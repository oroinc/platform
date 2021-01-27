<?php
declare(strict_types=1);

namespace Oro\Bundle\MigrationBundle\Command;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\MigrationBundle\Tools\SchemaDumper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Displays database schema as code.
 */
class DumpMigrationsCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'oro:migration:dump';

    private ManagerRegistry $registry;
    private SchemaDumper $schemaDumper;
    private ConfigManager $configManager;

    private array $bundles;
    protected array $allowedTables = [];
    protected array $extendedFieldOptions = [];

    protected ?string $namespace = null;
    protected ?string $className = null;
    protected string $version;

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

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function configure()
    {
        $this
            ->addOption('plain-sql', null, InputOption::VALUE_NONE, 'Output schema as plain SQL queries')
            ->addOption('bundle', null, InputOption::VALUE_OPTIONAL, 'Bundle to generate migration for')
            ->addOption('migration-version', null, InputOption::VALUE_OPTIONAL, 'Migration version', 'v1_0')
            ->setDescription('Displays database schema as code.')
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command displays the database schema as PHP code
that can be used to create a migration script.

  <info>php %command.full_name%</info>

The <info>--plain-sql</info> option can be used to output schema as plain SQL queries:

  <info>php %command.full_name% --plain-sql</info>

The <info>--bundle</info> option can be used to show only the portion of the schema
that is associated with the entities in a specific bundle:

  <info>php %command.full_name% --bundle=<bundle-name></info>

Use the <info>--migration-version</info> option to specify the migration version
for the generated PHP code:

  <info>php %command.full_name% --migration-version=<version-string></info>

HELP
            )
            ->addUsage('--plain-sql')
            ->addUsage('--bundle=<bundle-name>')
            ->addUsage('--migration-version=<version-string>')
        ;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
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

    protected function initializeBundleRestrictions(?string $bundle): void
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

    protected function initializeMetadataInformation(): void
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
     */
    protected function initializeExtendedFieldsOptions(ClassMetadata $classMetadata): void
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

    protected function getExtendedFieldOptions(string $className, string $fieldName): array
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

    protected function dumpPhpSchema(Schema $schema, OutputInterface $output): void
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
