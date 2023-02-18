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
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Debug\FileLinkFormatter;

/**
 * Displays database schema as code.
 */
class DumpMigrationsCommand extends Command
{
    private const GLOBAL_INSTALLATION_CLASSNAME = 'GlobalInstallation';

    private const SUCCESS_ACTION_CREATED = 'created';
    private const SUCCESS_ACTION_UPDATED = 'updated';

    /** @var string */
    protected static $defaultName = 'oro:migration:dump';

    private InputInterface $input;
    private OutputInterface $output;
    private SymfonyStyle $io;
    private FileLinkFormatter $fileLinkFormatter;

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
        array $bundles,
        ?string $fileLinkFormat = null
    ) {
        parent::__construct();

        $this->registry = $registry;
        $this->schemaDumper = $schemaDumper;
        $this->configManager = $configManager;
        $this->bundles = $bundles;
        $this->fileLinkFormatter = new FileLinkFormatter($fileLinkFormat);
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function configure()
    {
        $this
            ->addOption('plain-sql', null, InputOption::VALUE_NONE, 'Output schema as plain SQL queries')
            ->addOption('all', null, InputOption::VALUE_NONE, 'Generate migration for all bundles')
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
  
The <info>--all</info> option can be used to create migration script for all bundles:

  <info>php %command.full_name% --all</info>

The <info>--bundle</info> option can be used to show only the portion of the schema
that is associated with the entities in a specific bundle:

  <info>php %command.full_name% --bundle=<bundle-name></info>

Use the <info>--migration-version</info> option to specify the migration version
for the generated PHP code:

  <info>php %command.full_name% --migration-version=<version-string></info>

HELP
            )
            ->addUsage('--plain-sql')
            ->addUsage('--all')
            ->addUsage('--bundle=<bundle-name>')
            ->addUsage('--migration-version=<version-string>')
        ;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setup($input, $output);

        $bundle = $this->getBundleOption();
        $this->version = $input->getOption('migration-version');
        $this->initializeBundleRestrictions($bundle);
        $this->initializeMetadataInformation();

        if (!$this->initializeBundleMetadataRestrictions($bundle)) {
            return self::FAILURE;
        }

        $connection = $this->registry->getConnection();

        /** @var Schema $schema */
        $schema = $connection->getSchemaManager()->createSchema();

        if ($input->getOption('plain-sql')) {
            $sqls = $schema->toSql($connection->getDatabasePlatform());
            foreach ($sqls as $sql) {
                $output->writeln($sql . ';');
            }
        } else {
            $this->writePhpSchema($schema, $bundle);
        }

        return self::SUCCESS;
    }

    protected function setup(InputInterface $input, OutputInterface $output): void
    {
        $this->input = $input;
        $this->output = $output;

        $this->io = new SymfonyStyle($input, $output);
    }

    protected function getBundleOption(): ?string
    {
        $bundle = $this->input->getOption('bundle');
        $all = $this->input->getOption('all');

        if ($bundle) {
            return $bundle;
        }

        if (!$all) {
            return $this->askForBundleOption();
        }

        return null;
    }

    protected function askForBundleOption()
    {
        $helper = $this->getHelper('question');

        $bundles = array_keys($this->bundles);
        $question = new Question("\n<fg=green>Please enter the name of bundle:</> \n> ");
        $question->setAutocompleterValues($bundles);

        return $helper->ask($this->input, $this->output, $question);
    }

    protected function initializeBundleRestrictions(?string $bundle): void
    {
        if ($bundle) {
            if (!array_key_exists($bundle, $this->bundles)) {
                throw new \InvalidArgumentException(
                    sprintf('Bundle "%s" is not a known bundle', $bundle)
                );
            }

            /**
             * In the case where bundle class name and bundle folder have the same name
             * we need to replace only the bundle class name with 'Entity'
             *
             * Bundle\AcmeBundle\AcmeBundle -> Bundle\AcmeBundle\Entity
             */
            $this->namespace = rtrim($this->bundles[$bundle], $bundle) . 'Entity';
            $this->className = $bundle . 'Installer';
        } else {
            $this->className = self::GLOBAL_INSTALLATION_CLASSNAME;
        }
    }

    protected function initializeBundleMetadataRestrictions(?string $bundle): bool
    {
        if ($bundle && !$this->allowedTables) {
            $this->io->error('No related entities were found to '.$bundle);

            return false;
        }

        return true;
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

    protected function dumpPhpSchema(Schema $schema): string
    {
        $schema->visit($this->schemaDumper);

        return $this->schemaDumper->dump(
            $this->allowedTables,
            $this->namespace,
            $this->className,
            $this->version,
            $this->extendedFieldOptions
        );
    }

    protected function writePhpSchema(Schema $schema, ?string $bundle): void
    {
        $dump = $this->dumpPhpSchema($schema);

        if ($bundle) {
            $bundleClass = $this->bundles[$bundle];
            $bundleFile  = (new \ReflectionClass($bundleClass))->getFileName();

            $migrationFolder = dirname($bundleFile).'/Migrations/Schema';
        } else {
            $migrationFolder = $this->getApplication()->getKernel()->getProjectDir();
        }

        $filesystem = new Filesystem();

        $migrationFile = $migrationFolder.'/'.$this->className.'.php';
        $migrationExists = $filesystem->exists($migrationFile);
        if ($migrationExists) {
            if ($this->isOverwriteMigration($migrationFile)) {
                $filesystem->remove($migrationFile);
            } else {
                $this->writeCancelMessage();
                return;
            }
        }

        $filesystem->appendToFile($migrationFile, $dump);

        $successAction = $migrationExists ? self::SUCCESS_ACTION_UPDATED : self::SUCCESS_ACTION_CREATED;
        $this->writeSuccessMessage($successAction, $migrationFile);
    }

    protected function writeCancelMessage()
    {
        $this->io->newLine();
        $this->io->writeln(' <bg=yellow;fg=white>           </>');
        $this->io->writeln(' <bg=yellow;fg=white> Cancelled </>');
        $this->io->writeln(' <bg=yellow;fg=white>           </>');
    }

    protected function writeSuccessMessage(string $action, string $migrationPath)
    {
        $this->io->newLine();
        $this->io->writeln(sprintf('<fg=blue>%s</>: %s', $action, $this->generateFileLink($migrationPath)));

        $this->io->newLine();
        $this->io->writeln(' <bg=green;fg=white>          </>');
        $this->io->writeln(' <bg=green;fg=white> Success! </>');
        $this->io->writeln(' <bg=green;fg=white>          </>');
        $this->io->newLine();
    }

    protected function isOverwriteMigration(string $migrationFile): bool
    {
        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion(
            sprintf("\nDo you want to overwrite %s? [Y/n] ", $this->generateFileLink($migrationFile))
        );

        return $helper->ask($this->input, $this->output, $question);
    }

    protected function generateFileLink(string $file): string
    {
        $link = $this->fileLinkFormatter->format($file, 1);

        return $link ? sprintf('<href=%s>%s</>', $link, $file) : $file;
    }
}
