<?php

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

function Myownfreehost_API(array $params, $endpoint, array $data = [], $dontLog = false) {
    $prefix = $params['serverport'] == 2086 ? 'http://' : 'https://';
    $url = $prefix . $params['serverhostname'] . ':' . $params['serverport'] . $endpoint;

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    curl_setopt($curl, CURLOPT_TIMEOUT, 20);

    $headers = [
        "Authorization: Basic " . base64_encode($params['serverusername'] . ":" . $params['serverpassword'])
    ];
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

    $response = curl_exec($curl);
    $responseData = strpos($url, 'xml-api') !== false 
        ? json_decode(json_encode(simplexml_load_string($response)), true) 
        : json_decode($response, true);

    if ($responseData === false && !$dontLog) {
        logModuleCall("MyOwnFreeHost", "CURL ERROR", curl_error($curl), "");
    }

    curl_close($curl);

    if (!$dontLog) {
        logModuleCall(
            "MyOwnFreeHost",
            $endpoint,
            isset($data) ? $data : "",
            print_r($responseData, true)
        );
    }

    return $responseData;
}

function Myownfreehost_Error($func, $params, Exception $err) {
    logModuleCall("MyOwnFreeHost", $func, $params, $err->getMessage(), $err->getTraceAsString());
}

function Myownfreehost_MetaData() {
    return [
        "DisplayName" => "MyOwnFreeHost",
        "APIVersion" => "1.0",
        "DefaultNonSSLPort" => "2086",
        "DefaultSSLPort" => "2087",
        "ServiceSingleSignOnLabel" => "Login to cPanel",
        "AdminSingleSignOnLabel" => "Login to MOFH"
    ];
}

function Myownfreehost_ConfigOptions() {
    return [
        "WHM_Package_Name" => ["FriendlyName" => "WHM Package Name", "Type" => "text", "Size" => 25],
        "Web_Space_Quota" => ["FriendlyName" => "Web Space Quota", "Description" => "MB", "Type" => "text", "Size" => 10],
        "Bandwidth_Limit" => ["FriendlyName" => "Bandwidth Limit", "Description" => "MB", "Type" => "text", "Size" => 10],
        "Max_FTP_Accounts" => ["FriendlyName" => "Max FTP Accounts", "Type" => "text", "Size" => 10, "Default" => "1"],
        "Max_Email_Accounts" => ["FriendlyName" => "Max Email Accounts", "Type" => "text", "Size" => 10, "Default" => "None"],
        "Max_SQL_Databases" => ["FriendlyName" => "Max SQL Databases", "Type" => "text", "Size" => 10],
        "Max_Subdomains" => ["FriendlyName" => "Max Subdomains", "Type" => "text", "Size" => 10],
        "Max_Parked_Domains" => ["FriendlyName" => "Max Parked Domains", "Type" => "text", "Size" => 10],
        "Max_Addon_Domains" => ["FriendlyName" => "Max Addon Domains", "Type" => "text", "Size" => 10],
        "Cpanel" => ["FriendlyName" => "Cpanel Login Domain", "Description" => "Used for connection to the cPanel", "Type" => "text", "Size" => 10],
        "Lang" => ["FriendlyName" => "Cpanel Language", "Description" => "Set cPanel language, defaults to English", "Type" => "text", "Size" => 10]
    ];
}

function Myownfreehost_CreateAccount(array $params) {
    try {
        $postfields = [
            "username" => $params["username"],
            "password" => $params["password"],
            "domain" => $params["domain"],
            "savepkg" => 0,
            "quota" => Myownfreehost_GetOption($params, 'Web_Space_Quota'),
            "bwlimit" => Myownfreehost_GetOption($params, 'Bandwidth_Limit'),
            "contactemail" => $params['clientsdetails']['email'],
            "maxftp" => Myownfreehost_GetOption($params, 'Max_FTP_Accounts'),
            "maxsql" => Myownfreehost_GetOption($params, 'Max_SQL_Databases'),
            "maxpop" => Myownfreehost_GetOption($params, 'Max_Email_Accounts'),
            "maxsub" => Myownfreehost_GetOption($params, 'Max_Subdomains'),
            "parked" => Myownfreehost_GetOption($params, 'Max_Parked_Domains'),
            "maxaddon" => Myownfreehost_GetOption($params, 'Max_Addon_Domains'),
            "plan" => Myownfreehost_GetOption($params, 'WHM_Package_Name'),
            "api.version" => 1,
            "reseller" => 0
        ];

        $output = Myownfreehost_API($params, "/xml-api/createacct", $postfields);

        if ($output["result"]["status"] !== "1") {
            throw new Exception($output["result"]["statusmsg"]);
        }
    } catch (Exception $err) {
        return $err->getMessage();
    }

    return 'success';
}
