<?php
use WHMCS\Database\Capsule;
use WHMCS\Service\Service;
use WHMCS\View\Menu\Item as MenuItem;

add_hook("ClientAreaPrimarySidebar", 1, "Myownfreehost_defineSsoSidebarLinks");

function Myownfreehost_defineSsoSidebarLinks($sidebar)
{
    // Check if Service Details Actions exists
    if (!$sidebar->getChild("Service Details Actions")) {
        return null;
    }

    // Get service context using the newer method
    $service = Menu::context("service");
    
    // Verify service type and module
    if (!($service instanceof Service) || $service->product->module != "myownfreehost") {
        return null;
    }

    // Check permissions using the updated method
    $ssoPermission = checkContactPermission("productsso", true);

    // Get service credentials
    $username = $service->username;
    
    // Use the secure password decryption method
    $command = 'DecryptPassword';
    $postData = array('password2' => $service->password);
    $results = localAPI($command, $postData);
    $password = $results['password'];

    // Use prepared statements for security
    $result = Capsule::table('tblproducts')
        ->select('configoption10', 'configoption11')
        ->where('id', '=', $service->product->id)
        ->first();

    if (!$result) {
        return null;
    }

    $cpanelurl = $result->configoption10;
    $lang = $result->configoption11;

    // Create secure login form with CSRF protection
    $bodyhtml = '
    <form action="https://cpanel.' . htmlspecialchars($cpanelurl) . '/login.php" method="post" name="login" id="form-login">
        ' . generate_token("form") . '
        <input name="uname" id="mod_login_username" type="hidden" class="inputbox" value="' . htmlspecialchars($username) . '" />
        <input type="hidden" id="mod_login_password" name="passwd" class="inputbox" value="' . htmlspecialchars($password) . '"/>
        <input type="hidden" name="language" value="' . htmlspecialchars($lang) . '" />
        <input class="btn btn-success btn-sm btn-block" type="submit" value="' . Lang::trans('cpanellogin') . '"/>
    </form>';

    // Add menu items using the newer MenuItem approach
    $sidebar->getChild("Service Details Actions")
        ->addChild("Login to Webmail")
        ->setUri("http://185.27.134.244/roundcubemail/")
        ->setLabel(Lang::trans("cpanelwebmaillogin"))
        ->setAttributes(["target" => "_blank"])
        ->setDisabled($service->status != "Active")
        ->setOrder(3);

    $sidebar->getChild("Service Details Actions")
        ->addChild("Request Cancellation")
        ->setUri("clientarea.php?action=cancel&id=" . $service->id)
        ->setLabel(Lang::trans("cancellationrequested"))
        ->setDisabled($service->status != "Active")
        ->setOrder(4);

    // Add cPanel login section
    $cpanelLogin = $sidebar->addChild('cPanel Login')
        ->setLabel('cPanel Logins')
        ->setIcon('fa-server')
        ->setOrder(20)
        ->setFooterHtml($bodyhtml);

    return $sidebar;
}
