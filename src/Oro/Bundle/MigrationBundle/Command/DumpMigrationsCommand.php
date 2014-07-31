<?php

namespace Oro\Bundle\MigrationBundle\Command;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Tools\SchemaTool;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DumpMigrationsCommand extends ContainerAwareCommand
{
    /**
     * @var array
     */
    protected $tables = array();

    /**
     * @var string
     */
    protected $namespace;

    /**
     * @var string
     */
    protected $className;

    /**
     * @var string
     */
    protected $version;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('oro:migration:dump')
            ->addOption('plain-sql', null, InputOption::VALUE_NONE, 'Out schema as plain sql queries')
            ->addOption(
                'bundle',
                null,
                InputOption::VALUE_OPTIONAL,
                'Bundle name for which migration wll be generated'
            )
            ->addOption(
                'migration-version',
                null,
                InputOption::VALUE_OPTIONAL,
                'Migration version',
                'v1_0'
            )
            ->setDescription('Dump existing database structure.');
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->version = $input->getOption('migration-version');
        $this->initializeBundleRestrictions($input->getOption('bundle'));

        $schema = $this->getSchema();
        if ($input->getOption('plain-sql')) {
            /** @var Connection $connection */
            $connection = $this->getContainer()->get('doctrine')->getConnection();
            $sqls = $schema->toSql($connection->getDatabasePlatform());
            foreach ($sqls as $sql) {
                $output->writeln($sql . ';');
            }
        } else {
            $this->dumpPhpSchema($schema, $output);
        }
    }

    /**
     * @param string $bundle
     */
    protected function initializeBundleRestrictions($bundle)
    {
        if ($bundle) {
            $bundles = $this->getContainer()->getParameter('kernel.bundles');
            if (!array_key_exists($bundle, $bundles)) {
                throw new \InvalidArgumentException(
                    sprintf('Bundle "%s" is not a known bundle', $bundle)
                );
            }
            $this->namespace = str_replace($bundle, 'Entity', $bundles[$bundle]);
            $this->className = $bundle . 'Installer';
        }
    }

    /**
     * @return Schema
     * @throws \Doctrine\ORM\ORMException
     */
    protected function getSchema()
    {
        $doctrine = $this->getContainer()->get('doctrine');
        if ($this->namespace) {
            $entityManager = $doctrine->getManager();
            /** @var ClassMetadata[] $allMetadata */
            $allMetadata = $entityManager->getMetadataFactory()->getAllMetadata();
            $classes = array_filter(
                $allMetadata,
                function (ClassMetadata $entityMetadata) {
                    if ($entityMetadata->namespace == $this->namespace) {
                        $this->tables[$entityMetadata->getTableName()] = true;
                        return true;
                    }
                    return false;
                }
            );

            $schemaTool = new SchemaTool($entityManager);
            $schema = $schemaTool->getSchemaFromMetadata($classes);
        } else {
            $connection = $doctrine->getConnection();
            $schema = $connection->getSchemaManager()->createSchema();
        }

        return $schema;
    }

    /**
     * @param Schema $schema
     * @param OutputInterface $output
     */
    protected function dumpPhpSchema(Schema $schema, OutputInterface $output)
    {
        $visitor = $this->getContainer()->get('oro_migration.tools.schema_dumper');
        $schema->visit($visitor);

        $output->writeln(
            $visitor->dump(
                $this->tables,
                $this->namespace,
                $this->className,
                $this->version
            )
        );
    }
}
