<?php

namespace Oro\Bundle\MigrationBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

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

        $migrationLoader = $this->getContainer()->get('oro_migration.migrations.loader');
        $bundles         = $input->getOption('bundles');
        if (!empty($bundles)) {
            $migrationLoader->setBundles($bundles);
        }
        $excludeBundles = $input->getOption('exclude');
        if (!empty($excludeBundles)) {
            $migrationLoader->setExcludeBundles($excludeBundles);
        }

        $migrations = $migrationLoader->getMigrations();
        if (!empty($migrations)) {
            $migrationQueryBuilder = $this->getContainer()->get('oro_migration.migrations.query_builder');
            $queries               = $migrationQueryBuilder->getQueries($migrations);
            foreach ($queries as $item) {
                $output->writeln(sprintf('  <comment>> %s</comment>', $item['migration']));
                foreach ($item['queries'] as $sqlQuery) {
                    if ($input->getOption('show-queries')) {
                        $output->writeln(sprintf('    <info>%s</info>', $sqlQuery));
                    }
                    if (!$input->getOption('dry-run')) {
                        $migrationQueryBuilder->getConnection()->executeQuery($sqlQuery);
                    }
                }
            }
        }

        $output->writeln('Done.');
    }
}
