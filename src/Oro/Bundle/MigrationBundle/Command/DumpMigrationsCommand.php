<?php

namespace Oro\Bundle\MigrationBundle\Command;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\MigrationBundle\Tools\SchemaDumper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Dump existing database structure.
 */
class DumpMigrationsCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'oro:migration:dump';

    /**
     * @var ManagerRegistry
     */
    private $registry;

    /**
     * @var SchemaDumper
     */
    private $schemaDumper;

    /**
     * @var ConfigManager
     */
    private $configManager;

    /**
     * @var array
     */
    private $bundles;

    /**
     * @var array
     */
    protected $allowedTables = [];

    /**
     * @var array
     */
    protected $extendedFieldOptions = [];

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
     * @param ManagerRegistry $registry
     * @param SchemaDumper $schemaDumper
     * @param ConfigManager $configManager
     * @param array $bundles
     */
    public function __construct(
        ManagerRegistry $registry,
        SchemaDumper $schemaDumper,
        ConfigManager $configManager,
        array $bundles
    ) {
        parent::__construct();

        $this->registry = $registry;
        $this->schemaDumper = $schemaDumper;
        $this->configManager = $configManager;
        $this->bundles = $bundles;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->addOption('plain-sql', null, InputOption::VALUE_NONE, 'Out schema as plain sql queries')
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
        $this->initializeMetadataInformation();

        $connection = $this->registry->getConnection();

        /** @var Schema $schema */
        $schema = $connection->getSchemaManager()->createSchema();

        if ($input->getOption('plain-sql')) {
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
            if (!array_key_exists($bundle, $this->bundles)) {
                throw new \InvalidArgumentException(
                    sprintf('Bundle "%s" is not a known bundle', $bundle)
                );
            }
            $this->namespace = str_replace($bundle, 'Entity', $this->bundles[$bundle]);
            $this->className = $bundle . 'Installer';
        }
    }

    /**
     * Process metadata information.
     */
    protected function initializeMetadataInformation()
    {
        /** @var ClassMetadata[] $allMetadata */
        $allMetadata = $this->registry->getManager()->getMetadataFactory()->getAllMetadata();
        array_walk(
            $allMetadata,
            function (ClassMetadata $entityMetadata) {
                if ($this->namespace) {
                    if ($entityMetadata->namespace == $this->namespace) {
                        $this->allowedTables[$entityMetadata->getTableName()] = true;
                        foreach ($entityMetadata->getAssociationMappings() as $associationMappingInfo) {
                            if (!empty($associationMappingInfo['joinTable'])) {
                                $joinTableName = $associationMappingInfo['joinTable']['name'];
                                $this->allowedTables[$joinTableName] = true;
                            }
                        }
                        $this->initializeExtendedFieldsOptions($entityMetadata);
                    }
                } else {
                    $this->initializeExtendedFieldsOptions($entityMetadata);
                }
            }
        );
    }

    /**
     * Initialize extended field options by field.
     *
     * @param ClassMetadata $classMetadata
     */
    protected function initializeExtendedFieldsOptions(ClassMetadata $classMetadata)
    {
        $className = $classMetadata->getName();
        $tableName = $classMetadata->getTableName();
        foreach ($classMetadata->getFieldNames() as $fieldName) {
            if ($this->configManager->hasConfig($className, $fieldName)) {
                $columnName = $classMetadata->getColumnName($fieldName);
                $options = $this->getExtendedFieldOptions($className, $fieldName);
                if (!empty($options['extend']['is_extend'])) {
                    $this->extendedFieldOptions[$tableName][$columnName] = $options;
                }
            }
        }
    }

    /**
     * Get extended field options.
     *
     * @param string $className
     * @param string $fieldName
     * @return array
     */
    protected function getExtendedFieldOptions($className, $fieldName)
    {
        $config = [];
        foreach ($this->configManager->getProviders() as $provider) {
            $fieldId = $provider->getId($className, $fieldName);
            $extendedConfig = $this->configManager->getConfig($fieldId)->all();
            if (!empty($extendedConfig)) {
                $config[$provider->getScope()] = $extendedConfig;
            }
        }

        return $config;
    }

    /**
     * @param Schema          $schema
     * @param OutputInterface $output
     */
    protected function dumpPhpSchema(Schema $schema, OutputInterface $output)
    {
        $schema->visit($this->schemaDumper);

        $output->writeln(
            $this->schemaDumper->dump(
                $this->allowedTables,
                $this->namespace,
                $this->className,
                $this->version,
                $this->extendedFieldOptions
            )
        );
    }
}
