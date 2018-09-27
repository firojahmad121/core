<?php

namespace Webkul\UVDesk\CoreBundle\CLI;

use Doctrine\ORM\EntityManager;
use Doctrine\DBAL\DBALException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Console\Input\ArrayInput as ConsoleOptions;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\BufferedOutput;

class UpdateDatabaseSchema extends Command
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
        $this->setName('uvdesk:revise-schema');
        $this->setDescription('Syncronizes your database with latest schema definitions and loads default datasets.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln("\u2714\u274c\n");
        $output->writeln("\n<comment># Verifying database credentials</comment>\n");

        try {
            $databaseConnection = $this->entityManager->getConnection();

            if (false == $databaseConnection->isConnected()) {
                $databaseConnection->connect();
            }

            $output->writeln("-> Successfully established a connection with the <info>" . $databaseConnection->getDatabase() . "</info> database.\n");
        } catch (DBALException $e) {
            $exceptionMessage = $e->getMessage();
            $whitespaceRepeater = strlen($exceptionMessage) + 8;

            $output->writeln("  <bg=red>" . str_repeat(" ",  $whitespaceRepeater) . "</>");
            $output->writeln("  <bg=red;fg=white>    " . $exceptionMessage . "    </>");
            $output->writeln("  <bg=red>" . str_repeat(" ",  $whitespaceRepeater) . "</>");

            $output->writeln("\n  Please ensure that you have correctly configured the <comment>DATABASE_URL</comment> variable defined inside your <fg=blue;options=bold>.env</> environment file.");
            $output->writeln("");

            return;
        }

        $output->writeln("<comment># Running database update sequence</comment>\n");
        
        $this->versionMigrations($output);
        $this->compareMigrations($output);
        
        if ('0' != $this->getLatestMigrationVersion(new BufferedOutput())) {
            $output->writeln("\n-> Migrating database to the latest schema version.");
            $this->migrateDatabaseToLatestVersion($output);
        } else {
            $output->writeln("\n-> Database is already syncronized with the latest schema version.");
        }

        // Initialize entities with initial dataset
        $output->writeln("-> Seeding core entities with initial dataset.");
        $this->runDataFixtures($output);

        $output->writeln("\n");
    }

    /**
     * Syncronize migration versions entries in the version table.
     * 
     * @param OutputInterface   $consoleOutput
     * 
     * @return UpdateDatabaseSchema
    */
    private function versionMigrations(OutputInterface $consoleOutput)
    {
        $command = $this->getApplication()->find('doctrine:migrations:version');
        ($consoleOptions = new ConsoleOptions([
            'command' => 'migrations:version',
            '--add' => true,
            '--all' => true,
            '--quiet' => true
        ]))->setInteractive(false);

        // Execute command
        $consoleOutput->writeln("-> Syncronizing migration versions.");
        $command->run($consoleOptions, $consoleOutput);

        return $this;
    }

    /**
     * Compare current schema mapping information and generate a new migration class 
     * if any mappings are not correctly syncronized.
     * 
     * @param OutputInterface   $consoleOutput
     * 
     * @return UpdateDatabaseSchema
    */
    private function compareMigrations(OutputInterface $consoleOutput)
    {
        $compareMigrationsCommand = $this->getApplication()->find('doctrine:migrations:diff');
        $compareMigrationsCommandOptions = new ConsoleOptions([
            'command' => 'migrations:diff',
            '--quiet' => true
        ]);
        
        $viewMigrationStatusCommand = $this->getApplication()->find('doctrine:migrations:status');
        $viewMigrationStatusCommandOptions = new ConsoleOptions([
            'command' => 'migrations:status'
        ]);
            
        // Execute command
        $consoleOutput->writeln("-> Generating migrations by comparing your current database with available entity mapping information.");
        $compareMigrationsCommand->run($compareMigrationsCommandOptions, new NullOutput());
        $viewMigrationStatusCommand->run($viewMigrationStatusCommandOptions, $consoleOutput);

        return $this;
    }

    /**
     * Retrieve the latest migration version.
     * 
     * @param OutputInterface   $bufferedOutput
     * 
     * @return string
    */
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

    /**
     * Migrate database to the latest migration version.
     * 
     * @param OutputInterface   $consoleOutput
     * 
     * @return UpdateDatabaseSchema
    */
    private function migrateDatabaseToLatestVersion(OutputInterface $consoleOutput)
    {
        $command = $this->getApplication()->find('doctrine:migrations:migrate');
        ($commandOptions = new ConsoleOptions([
            'command' => 'migrations:migrate'
        ]))->setInteractive(false);
            
        $consoleOutput->writeln("");
        $command->run($commandOptions, $consoleOutput);

        $consoleOutput->writeln("");

        return $this;
    }

    /**
     * Seed core entities with default datasets.
     * 
     * @param OutputInterface   $consoleOutput
     * 
     * @return UpdateDatabaseSchema
    */
    private function runDataFixtures(OutputInterface $consoleOutput)
    {
        $command = $this->getApplication()->find('doctrine:fixtures:load');
        $commandOptions = new ConsoleOptions([
            'command' => 'fixtures:load',
            '--append' => true,
            '--quiet' => true
        ]);

        $command->run($commandOptions, $consoleOutput);

        return $this;
    }
}
