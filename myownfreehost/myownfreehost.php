<?php
if(!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

function Myownfreehost_API(array $params, array $data = []) {
    $url = 'https://panel.myownfreehost.net/xml-api/';
    
    // Add API credentials from module settings
    $data['api_user'] = $params['serverusername'];
    $data['api_key'] = $params['serverpassword'];
    $data['api_v'] = '1';  // API version

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($data),
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/x-www-form-urlencoded'
        ]
    ]);

    $response = curl_exec($curl);
    
    if (curl_errno($curl)) {
        $error = curl_error($curl);
        curl_close($curl);
        logModuleCall("MyOwnFreeHost", "API Error", $data, $error, "", ["api_key"]);
        throw new Exception("API Request Failed: " . $error);
    }

    curl_close($curl);
    
    // Parse XML response
    $xml = simplexml_load_string($response);
    if ($xml === false) {
        throw new Exception("Failed to parse API response");
    }
    
    $result = json_decode(json_encode($xml), true);
    
    // Log the API call (masking sensitive data)
    $logData = $data;
    unset($logData['api_key']);
    logModuleCall("MyOwnFreeHost", "API Call", $logData, $result, "", ["api_key"]);
    
    return $result;
}

function Myownfreehost_MetaData() {
    return [
        "DisplayName" => "MyOwnFreeHost",
        "APIVersion" => "1.0"
    ];
}

function Myownfreehost_ConfigOptions() {
    return [
        "Package_Name" => [
            "FriendlyName" => "Hosting Package",
            "Type" => "text",
            "Size" => 25,
            "Description" => "Enter the hosting package name",
        ],
        "Domain_TLD" => [
            "FriendlyName" => "Domain TLD",
            "Type" => "text",
            "Size" => 25,
            "Description" => "Default domain TLD (e.g., example.com)",
            "Default" => "mywebsite.com"
        ]
    ];
}

function Myownfreehost_CreateAccount(array $params) {
    try {
        // Required parameters
        $domain = $params['domain'];
        $username = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', substr($params['username'], 0, 8)));
        $password = $params['password'];
        $email = $params['clientsdetails']['email'];
        $package = $params['configoption1']; // Package name

        $apiData = [
            'username' => $username,
            'password' => $password,
            'domain' => $domain,
            'email' => $email,
            'plan' => $package,
            'action' => 'create'
        ];

        $result = Myownfreehost_API($params, $apiData);

        if (isset($result['status']) && $result['status'] == 'success') {
            return 'success';
        } else {
            throw new Exception($result['message'] ?? 'Unknown error occurred');
        }
    } catch(Exception $e) {
        return $e->getMessage();
    }
}

function Myownfreehost_SuspendAccount(array $params) {
    try {
        $apiData = [
            'username' => $params['username'],
            'action' => 'suspend',
            'reason' => $params['suspendreason'] ?? 'Suspended via WHMCS'
        ];

        $result = Myownfreehost_API($params, $apiData);

        if (isset($result['status']) && $result['status'] == 'success') {
            return 'success';
        } else {
            throw new Exception($result['message'] ?? 'Unknown error occurred');
        }
    } catch(Exception $e) {
        return $e->getMessage();
    }
}

function Myownfreehost_UnsuspendAccount(array $params) {
    try {
        $apiData = [
            'username' => $params['username'],
            'action' => 'unsuspend'
        ];

        $result = Myownfreehost_API($params, $apiData);

        if (isset($result['status']) && $result['status'] == 'success') {
            return 'success';
        } else {
            throw new Exception($result['message'] ?? 'Unknown error occurred');
        }
    } catch(Exception $e) {
        return $e->getMessage();
    }
}

function Myownfreehost_TerminateAccount(array $params) {
    try {
        $apiData = [
            'username' => $params['username'],
            'action' => 'delete'
        ];

        $result = Myownfreehost_API($params, $apiData);

        if (isset($result['status']) && $result['status'] == 'success') {
            return 'success';
        } else {
            throw new Exception($result['message'] ?? 'Unknown error occurred');
        }
    } catch(Exception $e) {
        return $e->getMessage();
    }
}

function Myownfreehost_ChangePassword(array $params) {
    try {
        $apiData = [
            'username' => $params['username'],
            'password' => $params['password'],
            'action' => 'password'
        ];

        $result = Myownfreehost_API($params, $apiData);

        if (isset($result['status']) && $result['status'] == 'success') {
            return 'success';
        } else {
            throw new Exception($result['message'] ?? 'Unknown error occurred');
        }
    } catch(Exception $e) {
        return $e->getMessage();
    }
}

function Myownfreehost_ClientArea($params) {
    return [
        "overrideDisplayTitle" => ucfirst($params["domain"]),
        "tabOverviewReplacementTemplate" => "templates/overview.tpl",
        'vars' => [
            'domain' => $params['domain'],
            'username' => $params['username'],
        ]
    ];
}
?>
