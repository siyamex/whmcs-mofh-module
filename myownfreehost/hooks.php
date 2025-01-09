<?php
// Hook.php

define("MOFH_BASE_URL", "https://cpanel.");

add_hook('ClientAreaPrimarySidebar', 1, function($primarySidebar) {
    $client = Menu::context('client');

    // Only show if the client is logged in
    if (!is_null($client)) {
        $cpanelurl = Capsule::table('tblhosting')->where('userid', $client->id)->value('configoption10');

        if ($cpanelurl) {
            $loginUrl = MOFH_BASE_URL . htmlspecialchars($cpanelurl) . "/login.php";
            $primarySidebar->addChild('mofhLogin', [
                'label' => 'Login to Control Panel',
                'uri' => $loginUrl,
                'order' => 100,
                'icon' => 'fa-cogs',
            ]);

            $webmailUrl = 'https://webmail.' . htmlspecialchars($cpanelurl);
            $primarySidebar->addChild('mofhWebmail', [
                'label' => 'Login to Webmail',
                'uri' => $webmailUrl,
                'order' => 101,
                'icon' => 'fa-envelope',
            ]);
        }
    }
});
