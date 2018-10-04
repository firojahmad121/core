<?php

namespace Webkul\UVDesk\CoreBundle\DependencyInjection;

abstract class HelpdeskExtension
{
    abstract public function loadDashboardItems();
    abstract public function loadNavigationItems();
}
