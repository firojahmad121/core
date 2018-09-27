<?php

namespace Webkul\UVDesk\CoreBundle\CLI;

use Doctrine\ORM\EntityManager;
use Doctrine\DBAL\DBALException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Console\Input\ArrayInput as ConsoleOptions;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\NullOutput;
use Webkul\UVDesk\CoreBundle\CLI\UTF8Symbol;
use Webkul\UVDesk\CoreBundle\CLI\ANSIEscapeSequence;

class CheckConfiguration extends Command
{
    private $container;
    private $entityManager;

    public function __construct(ContainerInterface $container, EntityManager $entityManager)
    {
        $this->container = $container;
        $this->entityManager = $entityManager;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('uvdesk:check-configs');
        $this->setDescription('Scans through your helpdesk setup to check for any mis-configurations.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /*
            1. Validate database configuration
            2. Check if fixtures have been loaded
            3. Check if super admin account has been created
            4. Check mail configuration
            5. Check default templates
        */

        $output->write(ANSIEscapeSequence::CLEAR_SCREEN);
        $output->write(ANSIEscapeSequence::MOVE_CURSOR_HOME);

        // Clearing the cache for the dev environment with debug true
        $output->writeln("\n\n    Examining the application setup for any mis-configuration issues...\n");

        // Check 1: Verify database connection
        $database = $this->entityManager->getConnection()->getDatabase();

        if (false == $this->isDatabaseConfigurationValid()) {
            $output->writeln([
                "\n <fg=red;>[MIS-CONFIG]</> <comment>Failed establishing a connection with </comment><info>$database</info><comment> database.</comment>",
                "\n Please ensure that you have correctly configured the <comment>DATABASE_URL</comment> variable defined inside your <fg=blue;options=bold>.env</> environment file.\n",
            ]);

            return;
        } else {
            $output->writeln("<info> [" . UTF8Symbol::CHECK . " ]  Successfully established connection with database</info>");
        }

        die;

        // Check 2: Ensure entities have been loaded
        $latestSchemaVersion = $this->compareMigrations($output)->getLatestMigrationVersion(new BufferedOutput());

        if ('0' != $latestSchemaVersion) {
            $output->writeln([
                "\n <fg=red;>[MIS-CONFIG]</> <comment>There are entities that have not been updated to the </comment><info>$database</info><comment> database yet.</comment>",
                "\n Please ensure that you have correctly configured the <comment>DATABASE_URL</comment> variable defined inside your <fg=blue;options=bold>.env</> environment file.\n",
            ]);

            return;
        }
        die;
    }

    private function compareMigrations(OutputInterface $consoleOutput)
    {
        $compareMigrationsCommand = $this->getApplication()->find('doctrine:migrations:diff');
        $compareMigrationsCommandOptions = new ConsoleOptions([
            'command' => 'migrations:diff',
            '--quiet' => true
        ]);
        
        $viewMigrationStatusCommand = $this->getApplication()->find('doctrine:migrations:status');
        $viewMigrationStatusCommandOptions = new ConsoleOptions([
            'command' => 'migrations:status',
            '--quiet' => true
        ]);
            
        // Execute command
        $compareMigrationsCommand->run($compareMigrationsCommandOptions, new NullOutput());
        $viewMigrationStatusCommand->run($viewMigrationStatusCommandOptions, new NullOutput());

        return $this;
    }

    private function getLatestMigrationVersion(OutputInterface $bufferedOutput)
    {
        $command = $this->getApplication()->find('doctrine:migrations:latest');
        $commandOptions = new ConsoleOptions([
            'command' => 'migrations:latest'
        ]);

        // Execute command
        $command->run($commandOptions, $bufferedOutput);

        return trim($bufferedOutput->fetch());
    }

    private function isDatabaseConfigurationValid()
    {
        $databaseConnection = $this->entityManager->getConnection();

        if (false === $databaseConnection->isConnected()) {
            try {    
                $databaseConnection->connect();
            } catch (DBALException $e) {
                return false;
            }
        }

        return true;
    }
}
