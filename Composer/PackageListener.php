<?php

namespace Webkul\UVDesk\CommunityBundle\Composer;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\Console\Output\ConsoleOutput;
use Webkul\UVDesk\PackageManager\Composer\ComposerPackageListener;

class PackageListener extends ComposerPackageListener
{
    public function onProjectCreated(Event $event)
    {
        $consoleOutput = new ConsoleOutput();
        $consoleOutputText = require __DIR__ . "/../Templates/CLI/on-boarding.php";
        
        $consoleOutput->write("$consoleOutputText\n");
    }

    public function onPackageUpdated(Event $event)
    {
        $path = getcwd() . "/config/packages/uvdesk.yaml";
        
        if (!file_exists($path)) {
            $yaml = Yaml::dump([
                'uvdesk' => [
                    'site.url' => 'localhost',
                    'email.domain' => '@localhost',
                    'debug' => true
                ]
            ]);

            file_put_contents($path, $yaml);
        }

        return;
    }

    public function onPackageRemoved(Event $event)
    {
        return;
    }
}
