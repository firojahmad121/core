<?php

namespace Webkul\UVDesk\CoreBundle\Package;

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\Console\Output\ConsoleOutput;
use Webkul\UVDesk\PackageManager\Composer\ComposerPackageListener;

class Composer extends ComposerPackageListener
{
    public function onProjectCreated(Event $event)
    {
        $consoleOutput = new ConsoleOutput();
        $consoleOutputText = require __DIR__ . "/../Templates/CLI/on-boarding.php";
        
        $consoleOutput->write("$consoleOutputText\n");
    }

    public function onPackageUpdated(Event $event)
    {
        $path_config = getcwd() . "/config/packages/uvdesk.yaml";
        $path_route = getcwd() . "/config/routes/uvdesk.yaml";
        
        if (!file_exists($path)) {
            file_put_contents($path, file_get_contents(__DIR__ . "/../Templates/config.yaml"));
        }

        if (!file_exists($path)) {
            file_put_contents($path, file_get_contents(__DIR__ . "/../Templates/routes.yaml"));
        }

        return;
    }

    public function onPackageRemoved(Event $event)
    {
        return;
    }
}
