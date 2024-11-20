<?php
use WHMCS\Database\Capsule;

add_hook("ClientAreaPrimarySidebar", -1, "Myownfreehost_defineSsoSidebarLinks");

function Myownfreehost_defineSsoSidebarLinks($sidebar) {
    if (!$sidebar->getChild("Service Details Actions")) {
        return null;
    }

    $service = Menu::context("service");

    // Ensure the service is valid and matches the MOFH module
    if (!($service instanceof WHMCS\Service\Service) || $service->product->module != "myownfreehost") {
        return null;
    }

    // Check SSO permission
    $ssoPermission = checkContactPermission("productsso", true);
    $username = $service->username;

    // Decrypt password
    $command = 'DecryptPassword';
    $postData = ['password2' => $service->password];
    $results = localAPI($command, $postData);
    $password = $results['password'] ?? null;

    if (!$password) {
        logModuleCall('MyOwnFreeHost', 'SSO', $service->id, 'Password decryption failed.');
        return null;
    }

    // Fetch cPanel URL and language settings
    $result = Capsule::select(Capsule::raw('SELECT configoption10, configoption11 FROM tblproducts WHERE id = ?', [$service->product->id]));
    $config = $result[0] ?? null;
    $cpanelurl = $config->configoption10 ?? '';
    $lang = $config->configoption11 ?? 'en';

    if (empty($cpanelurl)) {
        logModuleCall('MyOwnFreeHost', 'SSO', $service->id, 'cPanel URL not found.');
        return null;
    }

    // Build SSO form HTML
    $bodyhtml = '
    <form action="https://cpanel.' . htmlspecialchars($cpanelurl) . '/login.php" method="post" name="login" id="form-login">
        <input name="uname" type="hidden" value="' . htmlspecialchars($username) . '" />
        <input name="passwd" type="hidden" value="' . htmlspecialchars($password) . '" />
        <input name="language" type="hidden" value="' . htmlspecialchars($lang) . '" />
        <input class="btn btn-success btn-sm btn-block" type="submit" value="' . Lang::trans('cpanellogin') . '" />
    </form>';

    // Add links to the sidebar
    $sidebar->getChild("Service Details Actions")->addChild("Login to Webmail", [
        "uri" => "http://185.27.134.244/roundcubemail/",
        "label" => Lang::trans("cpanelwebmaillogin"),
        "attributes" => ["target" => "_blank"],
        "disabled" => $service->status != "Active",
        "order" => 3,
    ]);

    $sidebar->getChild("Service Details Actions")->addChild("Request Cancellation", [
        "uri" => "clientarea.php?action=cancel&id=" . $service->id,
        "label" => Lang::trans("cancellationrequested"),
        "disabled" => $service->status != "Active",
        "order" => 4,
    ]);

    $sidebar->addChild('cPanel Login', [
        'label' => 'cPanel Logins',
        'icon' => 'fa-server',
        'order' => 20,
        'footerHtml' => $bodyhtml,
    ]);
}
?>

