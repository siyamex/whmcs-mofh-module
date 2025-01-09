<?php
use WHMCS\Database\Capsule;
use WHMCS\Module\Addon\Myownfreehost\Myownfreehost; // Assuming namespace

add_hook("ClientAreaPrimarySidebar", -1, "Myownfreehost_defineSsoSidebarLinks");

function Myownfreehost_defineSsoSidebarLinks($sidebar) {
    if (!$sidebar->getChild("Service Details Actions")) {
        return null;
    }

    $service = Menu::context("service");
    if (!($service instanceof WHMCS\Service\Service) || $service->product->module !== "myownfreehost") {
        return null;
    }

    $username = $service->username;
    
    // Fetch encrypted password using a more secure method
    try{
        $command = 'DecryptPassword';
        $postData = array('password2' => $service->password);
        $results = localAPI($command, $postData);
        $password = $results['password'];
    } catch (Exception $e) {
        logModuleCall("Myownfreehost", "DecryptPassword Error", $postData, $e->getMessage());
        return;
    }
    

    // Retrieve config options with error handling
     try{
        $result = Capsule::select(Capsule::raw('SELECT configoption10, configoption11 FROM tblproducts WHERE id = :id'), ['id' => $service->product->id])[0];
        $cpanelurl = $result->configoption10;
        $lang = $result->configoption11;
     } catch(Exception $e) {
          logModuleCall("Myownfreehost", "Database Error", null, $e->getMessage());
         return;
     }

    $bodyhtml = '
        <form action="https://cpanel.' . htmlspecialchars($cpanelurl, ENT_QUOTES, 'UTF-8') . '/login.php" method="post" name="login" id="form-login">
            <input name="uname" id="mod_login_username" type="hidden" class="inputbox" alt="username" size="10" value="' . htmlspecialchars($username, ENT_QUOTES, 'UTF-8') . '" />
            <input type="hidden" id="mod_login_password" name="passwd" class="inputbox" size="10" alt="password" value="' . htmlspecialchars($password, ENT_QUOTES, 'UTF-8') . '"/>
            <input type="hidden" name="language" value="' . htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') . '" />
            <input class="btn btn-success btn-sm btn-block" type="submit" value="' . Lang::trans('cpanellogin') . '"/>
        </form>';

    $sidebar->getChild("Service Details Actions")->addChild("Login to Webmail", [
        "uri" => "http://185.27.134.244/roundcubemail/",
        "label" => Lang::trans("cpanelwebmaillogin"),
        "attributes" => ["target" => "_blank"],
        "disabled" => $service->status != "Active",
        "order" => 3
    ]);

    $sidebar->getChild("Service Details Actions")->addChild("Request Cancellation", [
        "uri" => "clientarea.php?action=cancel&id=" . $service->id,
        "label" => Lang::trans("cancellationrequested"),
        "disabled" => $service->status != "Active",
        "order" => 4
    ]);

    $sidebar->addChild('cPanel Login', [
        'label' => 'cPanel Logins',
        'icon' => 'fa-server',
        'order' => 20,
        'footerHtml' => $bodyhtml
    ]);
}
?>
