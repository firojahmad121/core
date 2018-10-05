<?php

namespace Webkul\UVDesk\CoreBundle\Package;

use Webkul\UVDesk\PackageManager\Composer\ComposerPackage;
use Webkul\UVDesk\PackageManager\Composer\ComposerPackageExtension;
use Webkul\UVDesk\CoreBundle\Package\HelpdeskExtension as CoreBundleExtension;

class Composer extends ComposerPackageExtension
{
    public function loadPackageConfiguration()
    {
        $composerPackage = new ComposerPackage(new CoreBundleExtension());
        $packageMessage = require __DIR__ . "/../Templates/CLI/on-boarding.php";

        $composerPackage
            ->writeToConsole($packageMessage)
            ->movePackageConfig('config/packages/uvdesk.yaml', 'Templates/config.yaml')
            ->movePackageConfig('config/routes/uvdesk.yaml', 'Templates/routes.yaml');
        
        return $composerPackage;
    }

    // public function onProjectCreated(Event $event)
    // {
    //     $consoleOutput = new ConsoleOutput();
    //     $consoleOutputText = require __DIR__ . "/../Templates/CLI/on-boarding.php";
        
    //     $consoleOutput->write("$consoleOutputText\n");
    // }

    // public function onPackageUpdated(Event $event)
    // {
    //     $path_config = getcwd() . "/config/packages/";
    //     $path_routes = getcwd() . "/config/routes/";
        
    //     // Move bundle configs to app packages configs
    //     if (!is_dir($path_config)) {
    //         mkdir($path_config);
    //     }

    //     if (!file_exists($path_config . "uvdesk.yaml")) {
    //         file_put_contents($path_config . "uvdesk.yaml", file_get_contents(__DIR__ . "/../Templates/config.yaml"));
    //     }

    //     // Move bundle routes to app routes
    //     if (!is_dir($path_routes)) {
    //         mkdir($path_routes);
    //     }

    //     if (!file_exists($path_routes . "uvdesk.yaml")) {
    //         file_put_contents($path_routes . "uvdesk.yaml", file_get_contents(__DIR__ . "/../Templates/routes.yaml"));
    //     }

    //     return;
    // }

    // public function onPackageRemoved(Event $event)
    // {
    //     return;
    // }
}
