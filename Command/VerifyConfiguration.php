<?php

namespace Webkul\UVDesk\CoreBundle\Command;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Console\Input\ArrayInput as ConsoleOptions;

class VerifyConfiguration extends Command
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
        $this->setName('uvdesk:check-config');
        $this->setDescription('Scans your helpdesk for any invalid or missing configuration data');
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

        die;

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
}
