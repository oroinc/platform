<?php
declare(strict_types=1);

namespace Oro\Bundle\EntityExtendBundle\Command;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Extend\EntityExtendUpdateProcessor;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Applies extend entity changes to the database schema.
 */
class UpdateCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'oro:entity-extend:update';

    private EntityExtendUpdateProcessor $entityExtendUpdateProcessor;
    private ConfigManager $configManager;

    public function __construct(
        EntityExtendUpdateProcessor $entityExtendUpdateProcessor,
        ConfigManager $configManager
    ) {
        parent::__construct();
        $this->entityExtendUpdateProcessor = $entityExtendUpdateProcessor;
        $this->configManager = $configManager;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    public function configure()
    {
        $this
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Display changes without applying them')
            ->setDescription('Applies extend entity changes to the database schema.')
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command applies extend entity changes
to the database schema and updates all related caches.

  <info>php %command.full_name%</info>

The <info>--dry-run</info> option can be used to print the changes without applying them:

  <info>php %command.full_name% --dry-run</info>

HELP
            )
            ->addUsage('--dry-run');
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        if ($input->getOption('dry-run')) {
            $this->showChanges($io);

            return 0;
        }

        return $this->applyChanges($io);
    }

    private function showChanges(SymfonyStyle $io): void
    {
        $changes = $this->getChanges();
        if (!$changes) {
            $io->success('There are no any changes.');

            return;
        }

        $io->text('The following entities have changes:');
        foreach ($changes as $entityClass => [$entityState, $fields]) {
            $io->newLine();
            $io->text(sprintf('%s    <comment>%s</comment>', $entityClass, $entityState));
            $io->text(str_repeat('-', strlen($entityClass) + strlen($entityState) + 4));
            if ($fields) {
                $io->text('Fields:');
                $fieldTable = new Table($io);
                $fieldTable->setStyle(
                    (new TableStyle())
                        ->setHorizontalBorderChars('')
                        ->setVerticalBorderChars('')
                        ->setDefaultCrossingChar('')
                );
                foreach ($fields as $fieldName => $fieldState) {
                    $fieldTable->addRow([$fieldName, sprintf('<comment>%s</comment>', $fieldState)]);
                }
                $fieldTable->render();
            }
        }

        $io->newLine();
        $io->text('To apply the changes run this command without <comment>--dry-run</comment> option.');
    }

    /**
     * @return array [entity class => [entity state, [field name => field stata, ...]], ...]
     */
    private function getChanges(): array
    {
        $changes = [];
        $configs = $this->configManager->getConfigs('extend');
        foreach ($configs as $config) {
            if ($this->isSchemaUpdateRequired($config)) {
                $entityClass = $config->getId()->getClassName();
                $fields = [];
                $fieldConfigs = $this->configManager->getConfigs('extend', $entityClass);
                foreach ($fieldConfigs as $fieldConfig) {
                    if (!$fieldConfig->is('state', ExtendScope::STATE_ACTIVE)) {
                        $fields[$fieldConfig->getId()->getFieldName()] = $fieldConfig->get('state');
                    }
                }
                ksort($fields);
                $changes[$entityClass] = [$config->get('state'), $fields];
            }
        }
        ksort($changes);

        return $changes;
    }

    private function isSchemaUpdateRequired(ConfigInterface $config): bool
    {
        return
            $config->is('is_extend')
            && !$config->is('state', ExtendScope::STATE_ACTIVE)
            && !$config->is('is_deleted');
    }

    private function applyChanges(SymfonyStyle $io): int
    {
        $io->text('Updating the database schema and all entity extend related caches ...');

        $updateResult = $this->entityExtendUpdateProcessor->processUpdate();
        if (false === $updateResult->isSuccessful()) {
            $failureMessage = 'The update failed.';
            if ($updateResult->getInternalFailureMessage()) {
                $failureMessage = $updateResult->getInternalFailureMessage();
            }

            $io->error($failureMessage);

            return 1;
        }

        $io->success('The update complete.');

        return 0;
    }
}
