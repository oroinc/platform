<?php
declare(strict_types=1);

namespace Oro\Bundle\EntityExtendBundle\Command;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityConfigBundle\Provider\ExtendEntityConfigProviderInterface;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Exception\RecoverableUpdateSchemaException;
use Oro\Bundle\EntityExtendBundle\Tools\EnumSynchronizer;
use Oro\Bundle\EntityExtendBundle\Tools\SchemaTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Updates database schema for extend entities.
 */
class UpdateSchemaCommand extends Command
{
    use SchemaTrait;

    /** @var string */
    protected static $defaultName = 'oro:entity-extend:update-schema';

    private ManagerRegistry $registry;
    private ExtendEntityConfigProviderInterface $extendEntityConfigProvider;
    private EnumSynchronizer $enumSynchronizer;
    private SchemaTool $schemaTool;

    public function __construct(
        ManagerRegistry $registry,
        ExtendEntityConfigProviderInterface $extendEntityConfigProvider,
        EnumSynchronizer $enumSynchronizer,
        SchemaTool $schemaTool
    ) {
        parent::__construct();

        $this->registry = $registry;
        $this->extendEntityConfigProvider = $extendEntityConfigProvider;
        $this->enumSynchronizer = $enumSynchronizer;
        $this->schemaTool = $schemaTool;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    public function configure()
    {
        $this
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Print the generated SQL instead of executing it')
            ->addOption(
                'skip-enum-sync',
                null,
                InputOption::VALUE_NONE,
                'Tells that you want process update schema only and skip enums sync.'
            )
            ->setDescription('Updates database schema for extend entities.')
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command updates database schema for extend entities.

  <info>php %command.full_name%</info>

The <info>--dry-run</info> option can be used to print the generated SQL statements instead
of applying them:

  <info>php %command.full_name% --dry-run</info>
  
The <info>--skip-enum-sync</info> option can be used to process update schema only and skip enums sync:

  <info>php %command.full_name% --skip-enum-sync</info>

HELP
            )
            ->addUsage('--dry-run')
            ->addUsage('--skip-enum-sync');
    }

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $symfonyStyle = new SymfonyStyle($input, $output);

        $symfonyStyle->write($this->getDescription(), true);

        $this->overrideRemoveNamespacedAssets();
        $this->overrideSchemaDiff();

        /** @var EntityManager $em */
        $em = $this->registry->getManager();

        $metadata = $this->getClassesMetadata($em);

        $sqls = $this->schemaTool->getUpdateSchemaSql($metadata, true);
        $sqlCount = count($sqls);

        if (0 === $sqlCount) {
            $symfonyStyle->write(
                'Nothing to update - a database is already in sync with the current entity metadata.',
                true
            );
        }

        if ($sqlCount && $input->getOption('dry-run')) {
            $symfonyStyle->write(implode(';' . PHP_EOL, $sqls) . ';', true);

            return 0;
        }

        if ($sqlCount) {
            try {
                $symfonyStyle->write('Updating database schema...', true);
                $this->schemaTool->updateSchema($metadata, true);
                $symfonyStyle->write(
                    sprintf(
                        'Database schema updated successfully! "<info>%s</info>" queries were executed',
                        count($sqls)
                    ),
                    true
                );
            } catch (RecoverableUpdateSchemaException $e) {
                $symfonyStyle->error('Failed to update the database schema! All changes in the schema were reverted.');

                return 1;
            }
        }

        if (!$input->getOption('skip-enum-sync')) {
            $symfonyStyle->write('Synchronizing enum related data for new enums...', true);
            $this->enumSynchronizer->sync();
            $symfonyStyle->write(
                'The process of synchronization enum related data for new enums has been successfully finished!',
                true
            );
        }

        return 0;
    }

    protected function getClassesMetadata(EntityManager $em): array
    {
        $extendConfigs = $this->extendEntityConfigProvider->getExtendEntityConfigs();
        $metadata = [];
        foreach ($extendConfigs as $extendConfig) {
            if (!$extendConfig->in('state', [ExtendScope::STATE_NEW, ExtendScope::STATE_DELETE])) {
                $metadata[] = $em->getClassMetadata($extendConfig->getId()->getClassName());
            }
        }

        return $metadata;
    }
}
