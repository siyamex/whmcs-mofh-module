<?php
if(!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

function Myownfreehost_API(array $params, $endpoint, array $data = [], $dontLog = false) {
    // Security improvement: Force HTTPS for all API calls
    $prefix = 'https://';
    $url = $prefix . filter_var($params['serverhostname'], FILTER_SANITIZE_URL) . ':' . 
           filter_var($params['serverport'], FILTER_SANITIZE_NUMBER_INT) . $endpoint;

    // Validate URL
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        throw new Exception("Invalid API URL");
    }

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_POST => !empty($data),
        CURLOPT_POSTFIELDS => $data,
        // Security improvement: Enable SSL verification
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_SSL_VERIFYPEER => true,
        // Add security headers
        CURLOPT_HTTPHEADER => [
            "Authorization: Basic " . base64_encode(
                filter_var($params['serverusername'], FILTER_SANITIZE_STRING) . ":" . 
                filter_var($params['serverpassword'], FILTER_SANITIZE_STRING)
            ),
            "Content-Type: application/json",
            "X-Request-Source: WHMCS"
        ]
    ]);

    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    
    if ($response === false) {
        $error = curl_error($curl);
        curl_close($curl);
        if (!$dontLog) {
            logModuleCall("MyOwnFreeHost", "CURL ERROR", $error, "", "", []);
        }
        throw new Exception("API Request Failed: " . $error);
    }

    curl_close($curl);

    // Parse response based on content type
    $need = 'xml-api';
    if (strpos($url, $need) !== false) {
        $responseData = Myownfreehost_ParseXMLResponse($response);
    } else {
        $responseData = json_decode($response, true);
    }

    if (!$dontLog) {
        // Security: Mask sensitive data before logging
        $logData = $data;
        if (isset($logData['password'])) $logData['password'] = '******';
        if (isset($logData['passwd'])) $logData['passwd'] = '******';
        
        logModuleCall(
            "MyOwnFreeHost",
            $endpoint,
            $logData,
            $responseData,
            "",
            ['password', 'passwd']
        );
    }

    return $responseData;
}

// New helper function for XML parsing
function Myownfreehost_ParseXMLResponse($response) {
    libxml_use_internal_errors(true);
    $xml = simplexml_load_string($response);
    if ($xml === false) {
        $errors = libxml_get_errors();
        libxml_clear_errors();
        throw new Exception("Failed to parse XML response: " . $errors[0]->message);
    }
    return json_decode(json_encode($xml), true);
}

function Myownfreehost_CreateAccount(array $params) {
    try {
        // Validate required parameters
        $requiredParams = ['username', 'password', 'domain'];
        foreach ($requiredParams as $param) {
            if (empty($params[$param])) {
                throw new Exception("Missing required parameter: {$param}");
            }
        }

        // Sanitize and validate inputs
        $postfields = [
            "username" => filter_var($params["username"], FILTER_SANITIZE_STRING),
            "password" => $params["password"],
            "domain" => filter_var($params["domain"], FILTER_SANITIZE_STRING),
            "savepkg" => 0,
            "quota" => filter_var(Myownfreehost_GetOption($params, 'Web_Space_Quota'), FILTER_SANITIZE_NUMBER_INT),
            "bwlimit" => filter_var(Myownfreehost_GetOption($params, 'Bandwidth_Limit'), FILTER_SANITIZE_NUMBER_INT),
            "contactemail" => filter_var($params['clientsdetails']['email'], FILTER_VALIDATE_EMAIL),
            "maxftp" => filter_var(Myownfreehost_GetOption($params, 'Max_FTP_Accounts'), FILTER_SANITIZE_NUMBER_INT),
            "maxsql" => filter_var(Myownfreehost_GetOption($params, 'Max_SQL_Databases'), FILTER_SANITIZE_NUMBER_INT),
            "maxpop" => filter_var(Myownfreehost_GetOption($params, 'Max_Email_Accounts'), FILTER_SANITIZE_STRING),
            "maxsub" => filter_var(Myownfreehost_GetOption($params, 'Max_Subdomains'), FILTER_SANITIZE_NUMBER_INT),
            "maxaddon" => filter_var(Myownfreehost_GetOption($params, 'Max_Addon_Domains'), FILTER_SANITIZE_NUMBER_INT),
            "plan" => filter_var(Myownfreehost_GetOption($params, 'WHM_Package_Name'), FILTER_SANITIZE_STRING),
            "api.version" => 1,
            "reseller" => 0
        ];

        $output = Myownfreehost_API($params, "/xml-api/createacct", $postfields);
        
        if (!isset($output["result"]["status"]) || $output["result"]["status"] !== "1") {
            throw new Exception($output["result"]["statusmsg"] ?? "Unknown error occurred");
        }
        
        return 'success';
    } catch(Exception $err) {
        Myownfreehost_Error(__FUNCTION__, $params, $err);
        return $err->getMessage();
    }
}

// ... Rest of the functions would follow similar security improvements
?>
