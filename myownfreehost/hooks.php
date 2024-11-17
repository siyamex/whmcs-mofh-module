<?php
// 1. SQL Injection Prevention
use WHMCS\Database\Capsule;
add_hook("ClientAreaPrimarySidebar", -1, "Myownfreehost_defineSsoSidebarLinks");
function Myownfreehost_defineSsoSidebarLinks($sidebar) {
    if (!$sidebar->getChild("Service Details Actions")) {
        return NULL;
    }
    $service = Menu::context("service");
    if ($service instanceof WHMCS\Service\Service && $service->product->module != "myownfreehost") {
        return NULL;
    }
    $ssoPermission = checkContactPermission("productsso", true);
    $username = $service->username;
    $command = 'DecryptPassword';
    $postData = array('password2' => $service->password);
    $results = localAPI($command, $postData);
    $password = $results['password'];
    
    // Fix 1: Use parameter binding instead of raw SQL
    $result = Capsule::table('tblproducts')
        ->select(['configoption10', 'configoption11'])
        ->where('id', '=', $service->product->id)
        ->first();
        
    if (!$result) {
        return NULL; // Fix 2: Add error handling
    }
    
    $cpanelurl = $result->configoption10;
    $lang = $result->configoption11;
    
    // Fix 3: Add input sanitization
    $username = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
    $password = htmlspecialchars($password, ENT_QUOTES, 'UTF-8');
    $cpanelurl = htmlspecialchars($cpanelurl, ENT_QUOTES, 'UTF-8');
    $lang = htmlspecialchars($lang, ENT_QUOTES, 'UTF-8');
    
    // Fix 4: Use HTTPS for form action
    $bodyhtml = '
    <form action="https://cpanel.'.$cpanelurl.'/login.php" method="post" name="login" id="form-login" >
        <input name="uname" id="mod_login_username" type="hidden" class="inputbox" alt="username" size="10" value="'.$username.'" />
        <input type="hidden" id="mod_login_password" name="passwd" class="inputbox" size="10" alt="password" value="'.$password.'"/>
        <input type="hidden" name="language" value="'.$lang.'" />
        <input class="btn btn-success btn-sm btn-block" type="submit" value="'.Lang::trans('cpanellogin').'"/>
    </form>';
    
    // Fix 5: Add URL validation for webmail
    $webmailUrl = "http://185.27.134.244/roundcubemail/";
    if (!filter_var($webmailUrl, FILTER_VALIDATE_URL)) {
        $webmailUrl = "#"; // Fallback URL if invalid
    }
    
    $sidebar->getChild("Service Details Actions")->addChild("Login to Webmail", 
        array(
            "uri" => $webmailUrl,
            "label" => Lang::trans("cpanelwebmaillogin"),
            "attributes" => array("target" => "_blank"),
            "disabled" => $service->status != "Active",
            "order" => 3
        )
    );
    
    $sidebar->getChild("Service Details Actions")->addChild("Request Cancellation",
        array(
            "uri" => "clientarea.php?action=cancel&id=" . (int)$service->id, // Fix 6: Cast ID to integer
            "label" => Lang::trans("cancellationrequested"),
            "disabled" => $service->status != "Active",
            "order" => 4
        )
    );
    
    $sidebar->addChild('cPanel Login',
        array(
            'label' => 'cPanel Logins',
            'icon' => 'fa-server',
            'order' => 20,
            'footerHtml' => $bodyhtml,
        )
    );
}
?>
