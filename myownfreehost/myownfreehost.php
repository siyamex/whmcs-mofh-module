<?php
if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

use WHMCS\Module\Server\MyOwnFreeHost\ApiClient;
use WHMCS\Exception\Module\InvalidConfiguration;

/**
 * Make API call to MyOwnFreeHost
 *
 * @param array $params Module parameters
 * @param array $data API request data
 * @return array
 * @throws Exception
 */
function Myownfreehost_API(array $params, array $data = []) {
    $url = 'https://panel.myownfreehost.net/xml-api/';
    
    // Validate required API credentials
    if (empty($params['serverusername']) || empty($params['serverpassword'])) {
        throw new InvalidConfiguration('API credentials are required');
    }

    // Add API credentials from module settings
    $data['api_user'] = $params['serverusername'];
    $data['api_key'] = $params['serverpassword'];
    $data['api_v'] = '1';  // API version

    try {
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
                'Content-Type: application/x-www-form-urlencoded',
                'User-Agent: WHMCS/' . App::getVersion()->getVersion()
            ]
        ]);

        $response = curl_exec($curl);
        
        if (curl_errno($curl)) {
            throw new Exception("API Request Failed: " . curl_error($curl));
        }

        curl_close($curl);
        
        // Parse XML response
        $xml = @simplexml_load_string($response);
        if ($xml === false) {
            throw new Exception("Failed to parse API response");
        }
        
        $result = json_decode(json_encode($xml), true);
        
        // Log the API call (masking sensitive data)
        logModuleCall(
            "MyOwnFreeHost",
            "API Call",
            array_merge($data, ['api_key' => '****']),
            $result,
            $response,
            [$params['serverpassword'], $data['api_key']]
        );
        
        return $result;
    } catch (Exception $e) {
        logModuleCall(
            "MyOwnFreeHost",
            "API Error",
            $data,
            $e->getMessage(),
            $e->getTraceAsString(),
            [$params['serverpassword']]
        );
        throw $e;
    }
}

function Myownfreehost_MetaData() {
    return [
        "DisplayName" => "MyOwnFreeHost",
        "APIVersion" => "1.1",
        "RequiresServer" => true
    ];
}

function Myownfreehost_ConfigOptions() {
    return [
        "Package_Name" => [
            "FriendlyName" => "Hosting Package",
            "Type" => "text",
            "Size" => 25,
            "Description" => "Enter the hosting package name",
            "Required" => true
        ],
        "Domain_TLD" => [
            "FriendlyName" => "Domain TLD",
            "Type" => "text",
            "Size" => 25,
            "Description" => "Default domain TLD (e.g., example.com)",
            "Default" => "mywebsite.com",
            "Required" => true
        ],
        "Panel_URL" => [
            "FriendlyName" => "Control Panel URL",
            "Type" => "text",
            "Size" => 50,
            "Description" => "Control panel URL for client access",
            "Default" => "https://cpanel.example.com",
            "Required" => true
        ]
    ];
}

function Myownfreehost_CreateAccount(array $params) {
    try {
        // Validate required parameters
        if (empty($params['domain']) || empty($params['username']) || empty($params['password'])) {
            throw new InvalidConfiguration('Missing required parameters');
        }

        // Required parameters with improved validation
        $domain = $params['domain'];
        $username = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', substr($params['username'], 0, 8)));
        $password = $params['password'];
        $email = $params['clientsdetails']['email'];
        $package = $params['configoption1']; // Package name

        // Validate username format
        if (strlen($username) < 3) {
            throw new Exception("Username must be at least 3 characters long");
        }

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
        }
        
        throw new Exception($result['message'] ?? 'Unknown error occurred');
    } catch (Exception $e) {
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
        }
        
        throw new Exception($result['message'] ?? 'Unknown error occurred');
    } catch (Exception $e) {
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
        }
        
        throw new Exception($result['message'] ?? 'Unknown error occurred');
    } catch (Exception $e) {
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
        }
        
        throw new Exception($result['message'] ?? 'Unknown error occurred');
    } catch (Exception $e) {
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
        }
        
        throw new Exception($result['message'] ?? 'Unknown error occurred');
    } catch (Exception $e) {
        return $e->getMessage();
    }
}

function Myownfreehost_ClientArea($params) {
    try {
        return [
            "overrideDisplayTitle" => ucfirst($params["domain"]),
            "tabOverviewReplacementTemplate" => "templates/overview.tpl",
            'templateVariables' => [
                'domain' => $params['domain'],
                'username' => $params['username'],
                'panel_url' => $params['configoption3'],
                'product_status' => $params['status'],
            ]
        ];
    } catch (Exception $e) {
        // Log error but don't expose to client
        logActivity("MyOwnFreeHost Client Area Error: " . $e->getMessage());
        return ["errorMsg" => "An error occurred while loading the client area."];
    }
}
