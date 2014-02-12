<?php

namespace Oro\Bundle\InstallerBundle\Command;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateStructureCommand extends ContainerAwareCommand
{
    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName('oro:installer:migration:load')
            ->setDescription('Execute migration scripts.')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Outputs list of migrations without apply them');
    }

    /**
     * @inheritdoc
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var Connection $connection */
        $connection = $this->getContainer()->get('doctrine')->getConnection();
        $queries    = $this->getContainer()->get('oro_installer.migrations.loader')->getMigrationsQueries();

        if ($input->getOption('dry-run')) {
            $this->outputMigrationQueries($output, $queries);
        } else {
            $this->processMigrationsQueries($output, $queries, $connection);
        }
    }

    /**
     * List the list of migration files with sql queries
     *
     * @param OutputInterface $output
     * @param array $queries
     */
    protected function outputMigrationQueries(OutputInterface $output, $queries)
    {
        $output->writeln('List of migrations:');

        foreach ($queries as $migrationClass => $sqlQueries) {
            $output->writeln(sprintf(' <comment>> %s</comment>', $migrationClass));
            foreach ($sqlQueries as $sqlQuery) {
                $output->writeln(sprintf('  <info>%s</info>', $sqlQuery));
            }
        }
    }

    /**
     * Process migrations
     *
     * @param OutputInterface $output
     * @param array $queries
     * @param Connection $connection
     */
    protected function processMigrationsQueries(OutputInterface $output, $queries, Connection $connection)
    {
        $output->writeln('Process migrations...');
        foreach ($queries as $migrationClass => $sqlQueries) {
            $output->writeln(sprintf(' <comment>></comment> <info>%s</info>', $migrationClass));
            foreach ($sqlQueries as $sqlQuery) {
                $connection->executeQuery($sqlQuery);
            }
        }
        $output->writeln('Done.');
    }
}
