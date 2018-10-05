<?php

namespace Webkul\UVDesk\CoreBundle\Package;

use Webkul\UVDesk\PackageManager\Composer\ComposerPackage;
use Webkul\UVDesk\PackageManager\Composer\ComposerPackageExtension;

class Composer extends ComposerPackageExtension
{
    public function loadPackageConfiguration()
    {
        $composerPackage = new ComposerPackage(new PackageConfiguration());
        $packageMessage = require __DIR__ . "/../Templates/CLI/on-boarding.php";

        $composerPackage
            ->movePackageConfig('config/packages/uvdesk.yaml', 'Templates/config.yaml')
            ->movePackageConfig('config/routes/uvdesk.yaml', 'Templates/routes.yaml')
            ->writeToConsole($packageMessage);
        
        return $composerPackage;
    }
}
