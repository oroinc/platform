<?php

namespace Oro\Bundle\MigrationBundle\Command;

use Doctrine\DBAL\Connection;
use Oro\Bundle\MigrationBundle\Command\Logger\OutputLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Oro\Bundle\MigrationBundle\Migration\Loader\MigrationsLoader;
use Oro\Bundle\MigrationBundle\Migration\MigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\MigrationQueryLoader;

class LoadMigrationsCommand extends ContainerAwareCommand
{
    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName('oro:migration:load')
            ->setDescription('Execute migration scripts.')
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Outputs list of migrations without apply them'
            )
            ->addOption(
                'show-queries',
                null,
                InputOption::VALUE_NONE,
                'Outputs list of database queries for each migration file'
            )
            ->addOption(
                'bundles',
                null,
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL,
                'A list of bundles to load data from. If option is not set, migrations will be taken from all bundles.'
            )
            ->addOption(
                'exclude',
                null,
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL,
                'A list of bundle names which migrations should be skipped'
            );
    }

    /**
     * @inheritdoc
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln($input->getOption('dry-run') ? 'List of migrations:' : 'Process migrations...');

        $migrationLoader = $this->getMigrationLoader($input);
        $migrations      = $migrationLoader->getMigrations();
        if (!empty($migrations)) {
            if ($input->getOption('dry-run') && !$input->getOption('show-queries')) {
                foreach ($migrations as $migration) {
                    $output->writeln(sprintf('  <comment>> %s</comment>', get_class($migration)));
                }
            } else {
                $migrationQueryLoader = $this->getMigrationQueryLoader($input);
                $queries              = $migrationQueryLoader->getQueries($migrations);
                $connection           = $migrationQueryLoader->getConnection();
                $queryLogger          = new OutputLogger(
                    $output,
                    true,
                    $input->getOption('show-queries') ? null : OutputInterface::VERBOSITY_QUIET,
                    '    '
                );
                foreach ($queries as $item) {
                    $output->writeln(sprintf('  <comment>> %s</comment>', $item['migration']));
                    foreach ($item['queries'] as $query) {
                        if ($query instanceof MigrationQuery) {
                            if ($input->getOption('dry-run')) {
                                $descriptions = $query->getDescription();
                                foreach ((array)$descriptions as $description) {
                                    $queryLogger->notice($description);
                                }
                            } else {
                                $query->execute($connection, $queryLogger);
                            }
                        } else {
                            $queryLogger->notice($query);
                            if (!$input->getOption('dry-run')) {
                                $connection->executeQuery($query);
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * @param InputInterface $input
     * @return MigrationsLoader
     */
    protected function getMigrationLoader(InputInterface $input)
    {
        $migrationLoader = $this->getContainer()->get('oro_migration.migrations.loader');
        $bundles         = $input->getOption('bundles');
        if (!empty($bundles)) {
            $migrationLoader->setBundles($bundles);
        }
        $excludeBundles = $input->getOption('exclude');
        if (!empty($excludeBundles)) {
            $migrationLoader->setExcludeBundles($excludeBundles);
        }

        return $migrationLoader;
    }

    /**
     * @param InputInterface $input
     * @return MigrationQueryLoader
     */
    protected function getMigrationQueryLoader(InputInterface $input)
    {
        return $this->getContainer()->get('oro_migration.migrations.query_loader');
    }
}
