<?php

namespace Webkul\UVDesk\CoreBundle\Package;

use Webkul\UVDesk\PackageManager\Extensions\HelpdeskExtension;

class PackageConfiguration extends HelpdeskExtension
{
    public function loadDashboardItems()
    {
        return [];
    }

    public function loadNavigationItems()
    {
        return [];
    }
}
