<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Command;

use Oro\Bundle\EmailBundle\Provider\EmailRenderer;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Twig\Sandbox\VariablesProvider;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Displays email template variables and entity field availability in email templates.
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
#[AsCommand(
    name: 'oro:debug:email:variables',
    description: 'Displays email template variables and entity field availability in email templates.'
)]
final class DebugEmailVariablesCommand extends Command
{
    public function __construct(
        private readonly DoctrineHelper $doctrineHelper,
        private readonly EmailRenderer $emailRenderer,
        private readonly VariablesProvider $emailVariablesProvider,
        private readonly ConfigManager $configManager,
        private readonly TranslatorInterface $translator,
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
        $this
            ->addOption(
                'entity-class',
                null,
                InputOption::VALUE_OPTIONAL,
                'Entity class to show variables for.'
            )
            ->addOption(
                'entity-id',
                null,
                InputOption::VALUE_OPTIONAL,
                'Entity ID (requires --entity-class). Adds a "Value" column to the entity variables table.'
            )
            ->addOption(
                'system',
                null,
                InputOption::VALUE_NONE,
                'Show system variables. Shown by default when no other options are provided.'
            )
            ->addOption(
                'unavailable',
                null,
                InputOption::VALUE_NONE,
                'Show entity fields not available in email templates.'
            )
            ->addOption(
                'all-entities',
                null,
                InputOption::VALUE_NONE,
                'Show entity variables (or unavailable fields) for all configured entities at once.'
            )
            ->addOption(
                'plain',
                null,
                InputOption::VALUE_NONE,
                'Output plain list without table formatting. Each line: <entityClass><TAB><fieldName>.'
            )
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command displays email template variables and entity field
availability information.

When called without any options it shows the system variables table:

  <info>php %command.full_name%</info>

Show system variables explicitly:

  <info>php %command.full_name% --system</info>

Show available entity variables for a specific entity:

  <info>php %command.full_name% --entity-class=Oro\Bundle\UserBundle\Entity\User</info>

Show available entity variables including rendered values (requires a real entity):

  <info>php %command.full_name% --entity-class=Oro\Bundle\UserBundle\Entity\User --entity-id=1</info>

Show entity variables for all configured entities:

  <info>php %command.full_name% --all-entities</info>

Show fields NOT available in email templates for a specific entity:

  <info>php %command.full_name% --entity-class=Oro\Bundle\UserBundle\Entity\User --unavailable</info>

Show fields NOT available in email templates for all entities:

  <info>php %command.full_name% --unavailable --all-entities</info>

Output as a plain tab-separated list (useful for piping):

  <info>php %command.full_name% --unavailable --all-entities --plain</info>

HELP
            );
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $showSystem = (bool) $input->getOption('system');
        $showUnavailable = (bool) $input->getOption('unavailable');
        $allEntities = (bool) $input->getOption('all-entities');
        $plain = (bool) $input->getOption('plain');
        $entityClass = $input->getOption('entity-class');
        $entityId = $input->getOption('entity-id');

        if (!$this->validateInputOptions($io, $entityClass, $entityId, $showSystem, $allEntities)) {
            return Command::FAILURE;
        }

        if (!$showSystem && !$showUnavailable && !$allEntities && $entityClass === null) {
            $showSystem = true;
        }

        if ($showSystem) {
            return $this->runSystemVariables($io, $plain);
        }

        if ($showUnavailable) {
            return $this->runUnavailableFields($io, $entityClass, $allEntities, $plain);
        }

        return $this->runEntityVariables($io, $entityClass, $allEntities, $entityId, $plain);
    }

    private function validateInputOptions(
        SymfonyStyle $io,
        ?string $entityClass,
        mixed $entityId,
        bool $showSystem,
        bool $allEntities,
    ): bool {
        if ($entityId !== null && $allEntities) {
            $io->error('Option --entity-id cannot be combined with --all-entities.');

            return false;
        }

        if ($entityClass !== null && $showSystem) {
            $io->error('Option --entity-class cannot be combined with --system.');

            return false;
        }

        return true;
    }

    private function runSystemVariables(SymfonyStyle $io, bool $plain): int
    {
        $definitions = $this->emailVariablesProvider->getSystemVariableDefinitions();

        if ($plain) {
            foreach (array_keys($definitions) as $variable) {
                $io->writeln('system' . "\t" . $variable);
            }

            return Command::SUCCESS;
        }

        $io->section('System Variables');

        $rows = [];
        foreach ($definitions as $variable => $definition) {
            $type = $definition['type'] ?? 'mixed';
            $value = $type === 'array'
                ? $this->emailRenderer->renderTemplate(
                    sprintf('{%% for key, val in system.%s %%}{{ key }}: {{ val }}{%% endfor %%}', $variable)
                )
                : $this->emailRenderer->renderTemplate(sprintf('{{ system.%s }}', $variable));

            $rows[] = [
                'system.' . $variable,
                $definition['label'] ?? 'N/A',
                $type,
                $value,
            ];
        }

        $io->table(['Name', 'Title', 'Type', 'Value'], $rows);

        return Command::SUCCESS;
    }

    private function runEntityVariables(
        SymfonyStyle $io,
        ?string $entityClass,
        bool $allEntities,
        mixed $entityId,
        bool $plain,
    ): int {
        $classNames = $this->resolveEntityClasses($io, $entityClass, $allEntities);
        if ($classNames === null) {
            return Command::FAILURE;
        }

        if (empty($classNames)) {
            $io->note('Provide --entity-class or --all-entities to show entity variables.');

            return Command::SUCCESS;
        }

        $allVariableDefinitions = $this->emailVariablesProvider->getEntityVariableDefinitions();

        foreach ($classNames as $className) {
            $entity = null;
            if ($entityId !== null) {
                $entity = $this->loadEntity($io, $className, $entityId);
                if ($entity === null) {
                    return Command::FAILURE;
                }
            }

            $variables = $allVariableDefinitions[$className] ?? [];

            if ($plain) {
                foreach (array_keys($variables) as $variable) {
                    $io->writeln($className . "\t" . $variable);
                }

                continue;
            }

            $this->outputEntityVariablesTable($io, $className, $variables, $entity);
        }

        return Command::SUCCESS;
    }

    private function outputEntityVariablesTable(
        SymfonyStyle $io,
        string $className,
        array $variables,
        ?object $entity,
    ): void {
        $io->section(sprintf('Available in email templates: %s', $className));

        if (empty($variables)) {
            $io->note('No email template variables found for this entity.');

            return;
        }

        $headers = ['Name', 'Title', 'Type'];
        if ($entity !== null) {
            $headers[] = 'Value';
        }

        $rows = [];
        foreach ($variables as $variable => $definition) {
            $rows[] = $this->buildEntityVariableRow($variable, $definition, $entity);
        }

        $io->table($headers, $rows);
    }

    private function buildEntityVariableRow(string $variable, array $definition, ?object $entity): array
    {
        $row = ['entity.' . $variable, $definition['label'], $definition['type']];

        if ($entity !== null) {
            $row[] = $definition['type'] !== 'image'
                ? $this->emailRenderer->renderTemplate(
                    sprintf('{{ entity.%s }}', $variable),
                    ['entity' => $entity]
                )
                : sprintf('(type "%s" skipped for CLI)', $definition['type']);
        }

        return $row;
    }

    private function runUnavailableFields(
        SymfonyStyle $io,
        ?string $entityClass,
        bool $allEntities,
        bool $plain,
    ): int {
        $classNames = $this->resolveEntityClasses($io, $entityClass, $allEntities);
        if ($classNames === null) {
            return Command::FAILURE;
        }

        if (empty($classNames)) {
            $io->note('Provide --entity-class or --all-entities to show unavailable fields.');

            return Command::SUCCESS;
        }

        foreach ($classNames as $className) {
            $rows = $this->collectUnavailableFieldRows($className);

            if ($plain) {
                foreach (array_keys($rows) as $fieldName) {
                    $io->writeln($className . "\t" . $fieldName);
                }

                continue;
            }

            $io->section(sprintf('NOT available in email templates: %s', $className));

            if (empty($rows)) {
                $io->note('All fields have available_in_template=true.');

                continue;
            }

            ksort($rows);
            $io->table(['Name', 'Title', 'Type'], array_values($rows));
        }

        return Command::SUCCESS;
    }

    /**
     * Returns a map of fieldName => [name, label, type] rows for fields not available in email templates.
     *
     * @return array<string, array{string, string, string}>
     */
    private function collectUnavailableFieldRows(string $className): array
    {
        $emailConfigProvider = $this->configManager->getProvider('email');
        $entityConfigProvider = $this->configManager->getProvider('entity');

        $rows = [];
        foreach ($emailConfigProvider->getConfigs($className) as $fieldConfig) {
            if ($fieldConfig->is('available_in_template')) {
                continue;
            }

            /** @var FieldConfigId $fieldId */
            $fieldId = $fieldConfig->getId();
            $fieldName = $fieldId->getFieldName();
            $label = $entityConfigProvider->hasConfig($className, $fieldName)
                ? $this->translator->trans(
                    (string) $entityConfigProvider->getConfig($className, $fieldName)->get('label')
                )
                : $fieldName;

            $rows[$fieldName] = ['entity.' . $fieldName, $label, $fieldId->getFieldType()];
        }

        return $rows;
    }

    /**
     * Returns the resolved list of entity class names, or null on validation failure.
     *
     * @return string[]|null
     */
    private function resolveEntityClasses(SymfonyStyle $io, ?string $entityClass, bool $allEntities): ?array
    {
        if ($allEntities) {
            $classNames = [];
            foreach ($this->configManager->getProvider('email')->getIds() as $id) {
                $classNames[] = $id->getClassName();
            }
            sort($classNames);

            return $classNames;
        }

        if ($entityClass !== null) {
            if (!class_exists($entityClass)) {
                $io->error(sprintf('Class "%s" does not exist.', $entityClass));

                return null;
            }

            return [$this->doctrineHelper->getEntityClass($entityClass)];
        }

        return [];
    }

    private function loadEntity(SymfonyStyle $io, string $entityClass, mixed $entityId): ?object
    {
        $entity = $this->doctrineHelper->getEntity($entityClass, $entityId);
        if ($entity === null) {
            $io->error(sprintf('Entity "%s" with id "%s" not found.', $entityClass, $entityId));
        }

        return $entity;
    }
}
