<?php

namespace Oro\Bundle\InstallerBundle\Command;

use Doctrine\Bundle\DoctrineBundle\Command\Proxy\DoctrineCommandHelper;
use Doctrine\Bundle\MigrationsBundle\Command\DoctrineCommand;
use Doctrine\DBAL\Connection;

use Doctrine\DBAL\Migrations\Tools\Console\Command\ExecuteCommand;
use Oro\Bundle\InstallerBundle\Migrations\DependendMigrationInterface;
use Oro\Bundle\InstallerBundle\Migrations\Structure\Version20;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Oro\Bundle\InstallerBundle\Migrations\Structure\Version10;


class MigrateStructureCommand extends ContainerAwareCommand
{
    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName('oro:migration:structure')
            ->setDescription('Execute platform application update commands and init platform assets.')
            ->addOption('em', null, InputOption::VALUE_OPTIONAL, 'The entity manager to use for this command.');
    }

    /**
     * @inheritdoc
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var Connection $connection */
        $connection = $this->getContainer()->get('doctrine')->getConnection();
        $sm         = $connection->getSchemaManager();
        $migrations = $this->getContainer()->get('oro_installer.migrations.loader')->getMigrations();
        foreach ($migrations as $migration) {
            $fromSchema = $sm->createSchema();
            $toSchema   = clone $fromSchema;
            $sqls =  $migration->up($toSchema);
            $sqls = array_merge($sqls, $fromSchema->getMigrateToSql($toSchema, $connection->getDatabasePlatform()));
            foreach ($sqls as $sql) {
                $output->writeln($sql);
                $connection->executeQuery($sql);
            }
        }
    }
}
