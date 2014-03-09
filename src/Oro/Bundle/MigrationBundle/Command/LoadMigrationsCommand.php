<?php

namespace Oro\Bundle\MigrationBundle\Command;

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
                $queries               = $migrationQueryLoader->getQueries($migrations);
                $connection            = $migrationQueryLoader->getConnection();
                foreach ($queries as $item) {
                    $output->writeln(sprintf('  <comment>> %s</comment>', $item['migration']));
                    foreach ($item['queries'] as $query) {
                        if ($input->getOption('show-queries')) {
                            $descriptions = $query instanceof MigrationQuery
                                ? $query->getDescription()
                                : $query;
                            foreach ((array)$descriptions as $description) {
                                $output->writeln(sprintf('    <info>%s</info>', $description));
                            }
                        }
                        if (!$input->getOption('dry-run')) {
                            if ($query instanceof MigrationQuery) {
                                $query->execute($connection);
                            } else {
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
