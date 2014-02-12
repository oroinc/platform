<?php

namespace Oro\Bundle\InstallerBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DumpDbStructureCommand extends ContainerAwareCommand
{
    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName('oro:migration:db_dump')
            ->setDescription('Dump existing db structure.');
    }

    /**
     * @inheritdoc
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $connection = $this->getContainer()->get('doctrine')->getConnection();
        $schema     = $connection->getSchemaManager()->createSchema();
        $sqls       = $schema->toSql($connection->getDatabasePlatform());
        foreach ($sqls as $sql) {
            $output->writeln($sql . ';');
        }
    }
}
