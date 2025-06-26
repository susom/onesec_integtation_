<?php

/** @var \Stanford\OneSecIntegtation\OneSecIntegtation $module */
header("Content-type: application/json");
try {
    $module->validateApiToken();
    $recordId = $module->createNewRecord();
    echo json_encode(array("status" => 'success', "message" => 'Record created successfully', 'record_id' => $recordId));
}catch (Exception $e) {

    $statusCode = http_response_code();
    if ($statusCode == 200) {
        http_response_code(404);
    }
    \REDCap::logEvent('Exception', $e->getMessage());
    echo json_encode(array("status" => 'error', "message" => $e->getMessage()));
}
