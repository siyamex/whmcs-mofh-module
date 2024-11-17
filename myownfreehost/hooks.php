<?php
use WHMCS\Database\Capsule;
use WHMCS\Service\Service;
use WHMCS\Product\Product;

// Add hook to create client area sidebar links
add_hook("ClientAreaPrimarySidebar", -1, "Myownfreehost_defineSsoSidebarLinks");

function Myownfreehost_defineSsoSidebarLinks($sidebar) {
    // Check if Service Details Actions menu exists
    if (!$sidebar->getChild("Service Details Actions")) {
        return;
    }

    // Get service context
    $service = Menu::context("service");
    if (!$service instanceof Service || $service->product->module !== "myownfreehost") {
        return;
    }

    try {
        // Get product settings from database
        $product = Product::find($service->product->id);
        if (!$product) {
            throw new Exception("Product not found");
        }

        // Create auto-login form for cPanel
        if ($service->status === 'Active') {
            // Decrypt password
            $command = 'DecryptPassword';
            $postData = ['password2' => $service->password];
            $results = localAPI($command, $postData);
            $password = $results['password'];

            // Get domain info from service
            $domain = $service->domain;

            // Build the cPanel login form
            $bodyHtml = sprintf(
                '<form action="https://%s/login.php" method="post" name="login" id="form-login" target="_blank">
                    <input name="uname" type="hidden" value="%s">
                    <input type="hidden" name="passwd" value="%s">
                    <input class="btn btn-success btn-sm btn-block" type="submit" value="%s">
                </form>',
                $domain,
                htmlspecialchars($service->username),
                htmlspecialchars($password),
                Lang::trans('cpanellogin')
            );

            // Add cPanel login section
            $sidebar->addChild('cPanel Login', [
                'label' => 'cPanel Access',
                'icon' => 'fa-server',
                'order' => 20,
                'footerHtml' => $bodyHtml,
            ]);

            // Add Webmail login
            $sidebar->getChild("Service Details Actions")->addChild("Webmail", [
                "uri" => "https://" . $domain . "/webmail",
                "label" => "Access Webmail",
                "attributes" => ["target" => "_blank"],
                "order" => 3
            ]);

            // Add File Manager
            $sidebar->getChild("Service Details Actions")->addChild("File Manager", [
                "uri" => "https://" . $domain . "/filemanager",
                "label" => "File Manager",
                "attributes" => ["target" => "_blank"],
                "order" => 4
            ]);

            // Add phpMyAdmin
            $sidebar->getChild("Service Details Actions")->addChild("phpMyAdmin", [
                "uri" => "https://" . $domain . "/phpmyadmin",
                "label" => "Database Manager",
                "attributes" => ["target" => "_blank"],
                "order" => 5
            ]);
        }

        // Add Cancellation Request link (available for all statuses)
        $sidebar->getChild("Service Details Actions")->addChild("Request Cancellation", [
            "uri" => "clientarea.php?action=cancel&id=" . $service->id,
            "label" => Lang::trans("cancellationrequested"),
            "order" => 6
        ]);

    } catch (Exception $e) {
        logActivity("MOFH Hook Error: " . $e->getMessage());
        return;
    }
}

// Add hook for client area page to customize service information display
add_hook("ClientAreaPage", 1, function($vars) {
    if (!isset($vars['modulename']) || $vars['modulename'] !== 'myownfreehost') {
        return;
    }

    // Add custom variables for the client area template
    return [
        'pagetitle' => 'Hosting Control Panel',
        'templatefile' => 'templates/myownfreehost/clientarea',
        'breadcrumb' => [
            'clientarea.php?action=productdetails&id=' . $vars['serviceid'] => 'Product Details',
        ],
    ];
});

// Add hook for after module create to handle any post-creation tasks
add_hook("AfterModuleCreate", 1, function($vars) {
    if ($vars['moduletype'] !== 'myownfreehost') {
        return;
    }

    try {
        // Log successful account creation
        logActivity(
            sprintf(
                "MOFH hosting account created successfully - Domain: %s, Username: %s",
                $vars['params']['domain'],
                $vars['params']['username']
            ),
            $vars['params']['clientsdetails']['userid']
        );

    } catch (Exception $e) {
        logActivity("MOFH Post-Creation Hook Error: " . $e->getMessage());
    }
});

// Add hook for after module suspend
add_hook("AfterModuleSuspend", 1, function($vars) {
    if ($vars['moduletype'] !== 'myownfreehost') {
        return;
    }

    try {
        // Log suspension
        logActivity(
            sprintf(
                "MOFH hosting account suspended - Domain: %s, Username: %s, Reason: %s",
                $vars['params']['domain'],
                $vars['params']['username'],
                $vars['params']['suspendreason']
            ),
            $vars['params']['clientsdetails']['userid']
        );

    } catch (Exception $e) {
        logActivity("MOFH Suspension Hook Error: " . $e->getMessage());
    }
});

// Add hook for after module unsuspend
add_hook("AfterModuleUnsuspend", 1, function($vars) {
    if ($vars['moduletype'] !== 'myownfreehost') {
        return;
    }

    try {
        // Log unsuspension
        logActivity(
            sprintf(
                "MOFH hosting account unsuspended - Domain: %s, Username: %s",
                $vars['params']['domain'],
                $vars['params']['username']
            ),
            $vars['params']['clientsdetails']['userid']
        );

    } catch (Exception $e) {
        logActivity("MOFH Unsuspension Hook Error: " . $e->getMessage());
    }
});

// Add hook for after module terminate
add_hook("AfterModuleTerminate", 1, function($vars) {
    if ($vars['moduletype'] !== 'myownfreehost') {
        return;
    }

    try {
        // Log termination
        logActivity(
            sprintf(
                "MOFH hosting account terminated - Domain: %s, Username: %s",
                $vars['params']['domain'],
                $vars['params']['username']
            ),
            $vars['params']['clientsdetails']['userid']
        );

    } catch (Exception $e) {
        logActivity("MOFH Termination Hook Error: " . $e->getMessage());
    }
});
?>
