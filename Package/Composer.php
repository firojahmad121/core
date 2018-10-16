<?php

namespace Webkul\UVDesk\CoreBundle\Package;

use Webkul\UVDesk\PackageManager\Composer\ComposerPackage;
use Webkul\UVDesk\PackageManager\Composer\ComposerPackageExtension;

class Composer extends ComposerPackageExtension
{
    public function loadConfiguration()
    {
        $composerPackage = new ComposerPackage(new UVDeskCoreConfiguration());
        $composerPackage
            ->movePackageConfig('config/packages/uvdesk.yaml', 'Templates/config.yaml')
            ->movePackageConfig('config/routes/uvdesk.yaml', 'Templates/routes.yaml')
            ->movePackageConfig('templates/uvdesk-base-email-template.html.twig', 'Templates/Emails/base.html.twig')
            ->combineProjectConfig('config/packages/security.yaml', 'Templates/security.yaml')
            ->writeToConsole(require __DIR__ . "/../Templates/CLI/on-boarding.php");
        
        return $composerPackage;
    }
}
