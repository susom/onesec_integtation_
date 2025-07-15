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
            if(empty($this->getProjectSetting('screening-instrument', $this->projectId))){
                throw new \Exception("Missing Screening Instrument");
            }
            $instrument = $this->getProjectSetting('screening-instrument', $this->projectId);
            $recordId = reset($response['ids']);
            $url = \REDCap::getSurveyLink($recordId, $instrument, '', 1, $this->projectId);
            $result = array('recordId' => $recordId, 'screening_url' => $url);
            return $result;
        }else{
            throw new \Exception("Record already exist!");
        }
    }

    public function getRecordExternalId($record)
    {
        $param= array('record_id' => $record, 'project_id' => $this->projectId);
        $response = \REDCap::getData($param);
        if(empty($response['errors'])){
            return $response[$record][$this->getFirstEventId()]['external_id'];
        }
    }
    public function redcap_survey_complete ( $project_id, $record = NULL, $instrument, $event_id, $group_id = NULL, $survey_hash, $response_id = NULL, $repeat_instance = 1 )
    {
        try{
            if($record && $instrument == $this->getProjectSetting('parental-consent-instrument') && $this->getProjectSetting('one-sec-url')){
                if(!$this->getProjectSetting('child-assent-instrument')){
                    throw new \Exception("Missing Screening Instrument");
                }
                $url = \REDCap::getSurveyLink($record, $this->getProjectSetting('child-assent-instrument'), '', 1, $project_id);

                \REDCap::logEvent("Assent URL: ", $url);
                // TODO make call to OneSec
                $client = new \GuzzleHttp\Client();

                $body = array(
                   'redcap_record_id' => $record,
                    'external_id' => $this->getRecordExternalId($record),
                    "message"=> "Parental Consent Completed",
                    "assent_url" => $url,
                    "timestamp" => date('Y-m-d H:i:s', time()),
                    "participant"=> "CHILD"
                );
                $response = $client->post($this->getProjectSetting('one-sec-url'), [
                    'debug' => false,
                    'form_params' => $body,
                    'headers' => [
                        'Content-Type' => 'application/x-www-form-urlencoded',
                        'Accept' => 'application/json'
                    ]
                ]);
                if ($response->getStatusCode() < 300) {
                    $data = json_decode($response->getBody());
                    \REDCap::logEvent("OneSec Acknowledgement ", json_encode($data));
                }

            }
        }catch (\Exception $e){
            \REDCap::logEvent("Exception: ", $e->getMessage());
        }
    }
}
