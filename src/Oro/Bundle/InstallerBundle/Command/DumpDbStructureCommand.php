<?php

namespace Oro\Bundle\InstallerBundle\Command;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Visitor\Graphviz;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Oro\Bundle\InstallerBundle\Migrations\Visitor\SchemaDumper;

class DumpDbStructureCommand extends ContainerAwareCommand
{
    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName('oro:migration:db_dump')
            ->addOption('plain-sql', null, InputOption::VALUE_NONE, 'Out schema as plain sql quries')
            ->setDescription('Dump existing db structure.');
    }

    /**
     * @inheritdoc
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $connection = $this->getContainer()->get('doctrine')->getConnection();
        /** @var Schema $schema */
        $schema     = $connection->getSchemaManager()->createSchema();


        if ($input->getOption('plain-sql')) {
            $sqls       = $schema->toSql($connection->getDatabasePlatform());
            foreach ($sqls as $sql) {
                $output->writeln($sql . ';');
            }
        } else {
            $this->dumpPhpSchema($schema, $output);
        }

    }

    protected function dumpPhpSchema(Schema $schema, $output)
    {
        $visitor = new SchemaDumper();
        $visitor->setTwig($this->getContainer()->get('twig'));
        $schema->visit($visitor);

        $output->writeln($visitor->dump());
    }
}
