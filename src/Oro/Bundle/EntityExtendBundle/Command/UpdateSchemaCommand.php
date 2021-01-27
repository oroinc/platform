<?php
declare(strict_types=1);

namespace Oro\Bundle\EntityExtendBundle\Command;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityConfigBundle\Provider\ExtendEntityConfigProviderInterface;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Tools\EnumSynchronizer;
use Oro\Bundle\EntityExtendBundle\Tools\SaveSchemaTool;
use Oro\Bundle\EntityExtendBundle\Tools\SchemaTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

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

    public function __construct(
        ManagerRegistry $registry,
        ExtendEntityConfigProviderInterface $extendEntityConfigProvider,
        EnumSynchronizer $enumSynchronizer
    ) {
        parent::__construct();

        $this->registry = $registry;
        $this->extendEntityConfigProvider = $extendEntityConfigProvider;
        $this->enumSynchronizer = $enumSynchronizer;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    public function configure()
    {
        $this
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Print the generated SQL instead of executing it')
            ->setDescription('Updates database schema for extend entities.')
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command updates database schema for extend entities.

  <info>php %command.full_name%</info>

The <info>--dry-run</info> option can be used to print the generated SQL statements instead
of applying them:

  <info>php %command.full_name% --dry-run</info>

HELP
            )
            ->addUsage('--dry-run')
        ;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln($this->getDescription());

        $this->overrideRemoveNamespacedAssets();
        $this->overrideSchemaDiff();

        /** @var EntityManager $em */
        $em = $this->registry->getManager();

        $metadata = $this->getClassesMetadata($em);

        $schemaTool = new SaveSchemaTool($em);
        $sqls       = $schemaTool->getUpdateSchemaSql($metadata, true);
        if (0 === count($sqls)) {
            $output->writeln('Nothing to update - a database is already in sync with the current entity metadata.');
        } else {
            if ($input->getOption('dry-run')) {
                $output->writeln(implode(';' . PHP_EOL, $sqls) . ';');
            } else {
                $output->writeln('Updating database schema...');
                $schemaTool->updateSchema($metadata, true);
                $output->writeln(
                    sprintf(
                        'Database schema updated successfully! "<info>%s</info>" queries were executed',
                        count($sqls)
                    )
                );
            }
        }

        if (!$input->getOption('dry-run')) {
            $this->enumSynchronizer->sync();
        }
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
