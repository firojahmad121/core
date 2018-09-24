<?php

namespace Webkul\UVDesk\CoreBundle\Command;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Console\Input\ArrayInput as ConsoleOptions;

class Bootstrap extends Command
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
        $this->setName('setup-project');
        $this->setDescription('Runs all the available fixtures to populate database with initial dataset.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Verify connection with the database
        $output->writeln("\n<comment>Verifying database credentials</comment>\n");

        try {
            $databaseConnection = $this->entityManager->getConnection();

            if (false == $databaseConnection->isConnected()) {
                $databaseConnection->connect();
            }

            $output->writeln("-> Successfully established a connection with the <info>" . $databaseConnection->getDatabase() . "</info> database.\n");
        } catch (\Doctrine\DBAL\DBALException $e) {
            $exceptionMessage = $e->getMessage();
            $whitespaceRepeater = strlen($exceptionMessage) + 8;

            $output->writeln("  <bg=red>" . str_repeat(" ",  $whitespaceRepeater) . "</>");
            $output->writeln("  <bg=red;fg=white>    " . $exceptionMessage . "    </>");
            $output->writeln("  <bg=red>" . str_repeat(" ",  $whitespaceRepeater) . "</>");

            $output->writeln("\n  Please ensure that you have correctly configured the <comment>DATABASE_URL</comment> variable defined inside your <fg=blue;options=bold>.env</> environment file.");
            $output->writeln("");

            return;
        }

        $output->writeln("<comment>Running database update sequence</comment>\n");
        $output->writeln("-> Syncronizing migration versions.");
        $this->syncronizeMigrationVersions();
        
        $output->writeln("-> Generating migrations by comparing your current database with available entity mapping information.");
        $this->compareMigrationVersions();
        
        if ('0' != $this->getLatestMigrationVersion()) {
            $output->writeln("\n-> Migrating database to the latest schema version.");
            $this->migrateDatabaseToLatestVersion();
        } else {
            $output->writeln("\n-> Database is already syncronized with the latest schema version.");
        }

        // Initialize entities with initial dataset
        $output->writeln("-> Seeding core entities with initial dataset.");
        $this->populateEntities();

        $output->writeln("\n");
    }

    protected function syncronizeMigrationVersions()
    {
        $consoleOutput = new \Symfony\Component\Console\Output\ConsoleOutput();

        // Syncronize migration versions entries in the version table.
        $command = $this->getApplication()->find('doctrine:migrations:version');
        $commandOptions = new ConsoleOptions(['command' => 'migrations:version', '--add' => true, '--all' => true, '--quiet' => true]);

        $commandOptions->setInteractive(false);
        $command->run($commandOptions, $consoleOutput);

        return $this;
    }

    protected function compareMigrationVersions()
    {
        $nullOutput = new \Symfony\Component\Console\Output\NullOutput();
        $consoleOutput = new \Symfony\Component\Console\Output\ConsoleOutput();
        $bufferedOutput = new \Symfony\Component\Console\Output\BufferedOutput();

        // Compare current schema mapping information and generate a new migration class if any mappings are not correctly syncronized
        $command = $this->getApplication()->find('doctrine:migrations:diff');
        $commandOptions = new ConsoleOptions(['command' => 'migrations:diff', '--quiet' => true]);

        $command->run($commandOptions, $nullOutput);

        // Get the current migration status
        $command = $this->getApplication()->find('doctrine:migrations:status');
        $commandOptions = new ConsoleOptions(['command' => 'migrations:status']);

        $command->run($commandOptions, $consoleOutput);

        return $this;
    }

    protected function migrateDatabaseToLatestVersion()
    {
        $consoleOutput = new \Symfony\Component\Console\Output\ConsoleOutput();

        $consoleOutput->writeln("");
        
        $command = $this->getApplication()->find('doctrine:migrations:migrate');
        $commandOptions = new ConsoleOptions(['command' => 'migrations:migrate']);

        $commandOptions->setInteractive(false);
        $command->run($commandOptions, $consoleOutput);

        $consoleOutput->writeln("");

        return $this;
    }

    protected function getLatestMigrationVersion()
    {
        $bufferedOutput = new \Symfony\Component\Console\Output\BufferedOutput();

        $command = $this->getApplication()->find('doctrine:migrations:latest');
        $commandOptions = new ConsoleOptions(['command' => 'migrations:latest']);

        $command->run($commandOptions, $bufferedOutput);
        $latestMigrationVersion = trim($bufferedOutput->fetch());

        return $latestMigrationVersion;
    }

    protected function populateEntities()
    {
        $consoleOutput = new \Symfony\Component\Console\Output\ConsoleOutput();

        $command = $this->getApplication()->find('doctrine:fixtures:load');
        $commandOptions = new ConsoleOptions(['command' => 'fixtures:load', '--append' => true]);

        $command->run($commandOptions, $consoleOutput);

        return $this;
    }
}
