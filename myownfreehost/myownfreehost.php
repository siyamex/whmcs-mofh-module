<?php

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

use WHMCS\Database\Capsule;

// This is a placeholder.  You will need to get this info from the
// API documentation for https://api.myownfreehost.net/
define("API_ENDPOINT_BASE", "https://api.myownfreehost.net/");

function MyOwnFreeHost_API_Request($endpoint, $method = 'GET', $data = [], $authData = []) {
    // --- This function will do all the work of calling the api ---
    // --- It will need to be updated to the specific api auth method ---
    
    $url = API_ENDPOINT_BASE . $endpoint;
    $curl = curl_init();

    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 20,
    ]);

     if ($authData) {
            // --- Example of basic auth, this may need to be updated per api doc ---
           curl_setopt($curl, CURLOPT_HTTPHEADER, ["Authorization: Basic " . base64_encode($authData["username"] . ":" . $authData["password"])]);
    }

    if ($method === 'POST') {
         curl_setopt($curl, CURLOPT_POST, true);
         curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
    }


    $response = curl_exec($curl);
    $error = curl_error($curl);

    if ($error) {
        logModuleCall("MyOwnFreeHost", "API Error", $url, $error);
         curl_close($curl);
         return false;
    }
    
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    
    curl_close($curl);
    
     if ($httpCode >= 200 && $httpCode < 300) {
            // --- Example of decoding JSON response (can change)---
           $responseData = json_decode($response, true);
             if ($responseData === null) {
                   logModuleCall("MyOwnFreeHost", "API Response Error", $url, "Cannot decode response: " . $response );
                return false;
             }
           return $responseData;

        } else {
             logModuleCall("MyOwnFreeHost", "API HTTP Error", $url, "HTTP Code: " . $httpCode . " Response: " . $response);
            return false;
        }

}

// --- Example function to create a user account, needs api docs ---
function MyOwnFreeHost_CreateAccount(array $params) {

  $authData = [ "username" => MyOwnFreeHost_GetOption($params, "api_username"), "password" => MyOwnFreeHost_GetOption($params, "api_password") ]; // This will need to be updated
  $postData = [
        "username" => $params["username"],
        "password" => $params["password"],
        "domain" => $params["domain"],
    ];
  
  $result = MyOwnFreeHost_API_Request('accounts/create', 'POST', $postData, $authData);

    if ($result === false) {
       return "Error creating account, please see module logs.";
   }

      // Based on what we know about the api return.
      if($result["status"] !== "success")
      {
        return $result["error_message"] ?? "Unknown error creating account, please check the logs.";
      }

    return "success";
}

// --- Example function to get a list of accounts, needs api docs ---
function MyOwnFreeHost_ListAccounts(array $params) {
   $authData = [ "username" => MyOwnFreeHost_GetOption($params, "api_username"), "password" => MyOwnFreeHost_GetOption($params, "api_password") ]; // This will need to be updated
     
    $result =  MyOwnFreeHost_API_Request('accounts', 'GET', [], $authData);

    if($result === false)
    {
        return "Error listing accounts, please see module logs.";
    }
        // Based on what we know about the api return.
     if($result["status"] !== "success")
     {
        return $result["error_message"] ?? "Unknown error listing accounts, please check the logs.";
     }

     return $result["accounts"];
}



// --- Example function to test connection to the api, needs api docs ---
function MyOwnFreeHost_TestConnection(array $params) {
    // This code assumes the api has a test endpoint, this will need to be updated.
    $authData = [ "username" => MyOwnFreeHost_GetOption($params, "api_username"), "password" => MyOwnFreeHost_GetOption($params, "api_password") ]; // This will need to be updated
    $result = MyOwnFreeHost_API_Request('test', 'GET', [], $authData);

     if ($result === false)
    {
         return [ "success" => false, "error" => "Error contacting api, please see module logs." ];
    }
    
    // Based on what we know about the api return.
   if($result["status"] !== "success")
    {
         return [ "success" => false, "error" => $result["error_message"] ?? "Unknown error, check logs" ];
    }

    return [ "success" => true ];
}

function Myownfreehost_Error($func, $params, Exception $err) {
    logModuleCall("MyOwnFreeHost", $func, $params, $err->getMessage(), $err->getTraceAsString());
}

function Myownfreehost_MetaData()
{
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
        "WHM_Package_Name" => [
            "FriendlyName" => "WHM Package Name",
            "Type" => "text",
            "Size" => 25,
        ],
        "Web_Space_Quota" => [
            "FriendlyName" => "Web Space Quota",
            "Description" => "MB",
            "Type" => "text",
            "Size" => 10,
        ],
        "Bandwidth_Limit" => [
            "FriendlyName" => "Bandwidth Limit",
            "Description" => "MB",
            "Type" => "text",
            "Size" => 10,
        ],
        "Max_FTP_Accounts" => [
            "FriendlyName" => "Max FTP Accounts",
            "Type" => "text",
            "Size" => 10,
            "Default" => "1",
        ],
        "Max_Email_Accounts" => [
            "FriendlyName" => "Max Email Accounts",
            "Type" => "text",
            "Size" => 10,
            "Default" => "None",
        ],
        "Max_SQL_Databases" => [
            "FriendlyName" => "Max SQL Databases",
            "Type" => "text",
            "Size" => 10,
        ],
        "Max_Subdomains" => [
            "FriendlyName" => "Max Subdomains",
            "Type" => "text",
            "Size" => 10,
        ],
        "Max_Parked_Domains" => [
            "FriendlyName" => "Max Parked Domains",
            "Type" => "text",
            "Size" => 10,
        ],
        "Max_Addon_Domains" => [
            "FriendlyName" => "Max Addon Domains",
            "Type" => "text",
            "Size" => 10,
        ],
        "Cpanel" => [
            "FriendlyName" => "Cpanel Login Domain",
            "Description" => "Use for connection to the cpanel",
            "Type" => "text",
            "Size" => 10,
        ],
        "Lang" => [
            "FriendlyName" => "Cpanel Language",
            "Description" => "Use to set the language that cpanel should display if the value is empty the English language will be used",
            "Type" => "text",
            "Size" => 10,
        ],
        "api_username" => [
            "FriendlyName" => "API Username",
            "Type" => "text",
            "Size" => 25,
        ],
         "api_password" => [
            "FriendlyName" => "API Password",
            "Type" => "text",
            "Size" => 25,
        ],
    ];
}

function Myownfreehost_GetOption(array $params, $id, $default = null) {
    $options = Myownfreehost_ConfigOptions();

    $friendlyName = $options[$id]['FriendlyName'];
    if (isset($params['configoptions'][$friendlyName]) && $params['configoptions'][$friendlyName] !== '') {
        return $params['configoptions'][$friendlyName];
    } else if (isset($params['configoptions'][$id]) && $params['configoptions'][$id] !== '') {
        return $params['configoptions'][$id];
    } else if (isset($params['customfields'][$friendlyName]) && $params['customfields'][$friendlyName] !== '') {
        return $params['customfields'][$friendlyName];
    } else if (isset($params['customfields'][$id]) && $params['customfields'][$id] !== '') {
        return $params['customfields'][$id];
    }

    $found = false;
    $i = 0;
    foreach(Myownfreehost_ConfigOptions() as $key => $value) {
        $i++;
        if($key === $id) {
            $found = true;
            break;
        }
    }

    if($found && isset($params['configoption' . $i]) && $params['configoption' . $i] !== '') {
        return $params['configoption' . $i];
    }

    return $default;
}
function Myownfreehost_CreateAccount(array $params) {

     try
    {
            return MyOwnFreeHost_CreateAccount($params);
    }
       catch(Exception $err) {
        return $err->getMessage();
    }
}


function Myownfreehost_SuspendAccount(array $params) {
   try
    {
       $authData = [ "username" => MyOwnFreeHost_GetOption($params, "api_username"), "password" => MyOwnFreeHost_GetOption($params, "api_password") ]; // This will need to be updated
    $result =  MyOwnFreeHost_API_Request("/accounts/suspend", "POST", [ "username" => $params["username"] ], $authData);

      if($result === false)
        {
            return "Error listing accounts, please see module logs.";
        }

        if($result["status"] !== "success")
        {
           return $result["error_message"] ?? "Unknown error listing accounts, please check the logs.";
        }
        return "success";
    }
    catch(Exception $err)
    {
         return $err->getMessage();
    }
}


function Myownfreehost_UnsuspendAccount(array $params) {

     try
    {
         $authData = [ "username" => MyOwnFreeHost_GetOption($params, "api_username"), "password" => MyOwnFreeHost_GetOption($params, "api_password") ]; // This will need to be updated
        $result =  MyOwnFreeHost_API_Request("/accounts/unsuspend", "POST", [ "username" => $params["username"] ], $authData);

      if($result === false)
        {
             return "Error listing accounts, please see module logs.";
        }

      if($result["status"] !== "success")
      {
        return $result["error_message"] ?? "Unknown error listing accounts, please check the logs.";
      }

       return "success";
    }
      catch(Exception $err)
    {
         return $err->getMessage();
    }
}


function Myownfreehost_TerminateAccount(array $params) {

      try
    {
        $authData = [ "username" => MyOwnFreeHost_GetOption($params, "api_username"), "password" => MyOwnFreeHost_GetOption($params, "api_password") ]; // This will need to be updated
        $result =  MyOwnFreeHost_API_Request("/accounts/terminate", "POST", [ "username" => $params["username"] ], $authData);

    if($result === false)
    {
           return "Error listing accounts, please see module logs.";
     }
      if($result["status"] !== "success")
     {
        return $result["error_message"] ?? "Unknown error listing accounts, please check the logs.";
      }

     return "success";
    }
      catch(Exception $err)
    {
         return $err->getMessage();
    }
}
function Myownfreehost_ChangePassword(array $params) {

     try
    {
            $authData = [ "username" => MyOwnFreeHost_GetOption($params, "api_username"), "password" => MyOwnFreeHost_GetOption($params, "api_password") ]; // This will need to be updated
           $result =  MyOwnFreeHost_API_Request("/accounts/changepassword", "POST", [ "username" => $params["username"], "password" => $params["password"] ], $authData);

    if($result === false)
    {
          return "Error listing accounts, please see module logs.";
     }
      if($result["status"] !== "success")
     {
         return $result["error_message"] ?? "Unknown error listing accounts, please check the logs.";
      }

    return "success";
    }
      catch(Exception $err)
    {
         return $err->getMessage();
    }
}


function Myownfreehost_ChangePackage(array $params) {

       try
    {
          $authData = [ "username" => MyOwnFreeHost_GetOption($params, "api_username"), "password" => MyOwnFreeHost_GetOption($params, "api_password") ]; // This will need to be updated
          $result =  MyOwnFreeHost_API_Request("/accounts/changepackage", "POST", [ "username" => $params["username"], "package" => MyOwnFreeHost_GetOption($params, 'WHM_Package_Name') ], $authData);

    if($result === false)
      {
          return "Error listing accounts, please see module logs.";
       }
      if($result["status"] !== "success")
     {
        return $result["error_message"] ?? "Unknown error listing accounts, please check the logs.";
      }

    return "success";
    }
    catch(Exception $err)
    {
         return $err->getMessage();
    }
}
function Myownfreehost_SingleSignOn($params)
{
	$cpanel = Myownfreehost_GetOption($params, 'Cpanel');
	$link = "https://cpanel." . $cpanel;
	return array( "success" => true, "redirectTo" => $link);
}
function Myownfreehost_ServiceSingleSignOn($params)
{
    return Myownfreehost_SingleSignOn($params);
}
function Myownfreehost_AdminSingleSignOn($params)
{
    return array( "success" => true, "redirectTo" => 'https://panel.myownfreehost.net' );
}
function Myownfreehost_ClientArea($params)
{
    return array( "overrideDisplayTitle" => ucfirst($params["domain"]), "tabOverviewReplacementTemplate" => "../cpanel/templates/overview.tpl", 'vars' => [ 'cpanelurl' => $cpanel, 'lang' => $lang,]);
}
