<?php

namespace Stanford\OneSecIntegtation;

class OneSecIntegtation extends \ExternalModules\AbstractExternalModule
{
    private $projectId = null;

    private $apiToken = null;

    private \Project $project;
    public function __construct()
    {
        parent::__construct();
        // Other code to run when object is instantiated
        $this->projectId = $this->getSystemSetting('redcap-project-id');
        $this->apiToken = $this->getSystemSetting('redcap-api-token');
        $this->project = new \Project($this->projectId);
    }

    public function getAuthorizationHeader()
    {
        $headers = null;

        if (isset($_SERVER['Authorization'])) {
            $headers = trim($_SERVER["Authorization"]);
        } else if (isset($_SERVER['HTTP_AUTHORIZATION'])) { // Nginx or fast CGI
            $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
        } elseif (function_exists('apache_request_headers')) {
            $requestHeaders = apache_request_headers();
            // Server-side case-insensitive header check
            foreach ($requestHeaders as $key => $value) {
                if (strtolower($key) === 'authorization') {
                    $headers = trim($value);
                    break;
                }
            }
        }

        return $headers;
    }


// Function to validate token
    public function validateApiToken()
    {

        $authHeader = $this->getAuthorizationHeader();

        if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            throw new \Exception("Unauthorized: Missing or invalid Authorization header");
        }

        $token = $matches[1];

        if ($token !== $this->apiToken) {

            throw new \Exception("Forbidden: Invalid API token");
        }

        return true;
        // Token is valid, continue with request
    }

    public function createNewRecord()
    {

        if(!isset($_REQUEST["external_id"])){
            throw new \Exception("Missing or invalid request");
        }
        $externalId = htmlspecialchars($_REQUEST["external_id"]);
        $filter = "[external_id] = '" . $externalId . "'";
        $param = array('filterLogic' => $filter, 'project_id' => $this->projectId);

        $records = \REDCap::getData($param);

        if(empty($records)){
            $data = [];
            $data[$this->project->table_pk] = \REDCap::reserveNewRecordId($this->projectId);
            $data['external_id'] = $externalId;
            $response = \REDCap::saveData($this->projectId, 'json', json_encode(array($data)));
            if(!empty($response['errors'])){
                if (is_array($response['errors'])) {
                    throw new \Exception(implode(",", $response['errors']));
                } else {
                    throw new \Exception($response['errors']);
                }
            }
            // TODO redirect to screening survey
            return reset($response['ids']);
        }else{
            throw new \Exception("Records already exist");
        }
    }
}
