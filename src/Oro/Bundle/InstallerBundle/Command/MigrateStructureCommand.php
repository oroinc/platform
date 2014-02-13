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
        /** @var Connection $connection */
        $connection = $this->getContainer()->get('doctrine')->getConnection();
        $migrationLoader = $this->getContainer()->get('oro_installer.migrations.loader');
        $bundles = $input->getOption('bundles');
        if (!empty($bundles)) {
            $migrationLoader->setBundles($bundles);
        }
        $excludeBundles = $input->getOption('exclude');
        if (!empty($excludeBundles)) {
            $migrationLoader->setExcludeBundles($excludeBundles);
        }
        $queries    = $migrationLoader->getMigrationsQueries();
        $output->writeln($input->getOption('dry-run') ? 'List of migrations:' : 'Process migrations...');
        foreach ($queries as $migrationClass => $sqlQueries) {
            $output->writeln(sprintf('<comment>> %s</comment>', $migrationClass));
            foreach ($sqlQueries as $sqlQuery) {
                if ($input->getOption('show-queries')) {
                    $output->writeln(sprintf('  <info>%s</info>', $sqlQuery));
                }
                if (!$input->getOption('dry-run')) {
                    $connection->executeQuery($sqlQuery);
                }
            }
        }
        $output->writeln('Done.');
    }
}
