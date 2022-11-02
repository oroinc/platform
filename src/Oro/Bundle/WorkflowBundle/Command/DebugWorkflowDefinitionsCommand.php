<?php

declare(strict_types=1);

namespace Oro\Bundle\WorkflowBundle\Command;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfiguration;
use Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowDefinitionRepository;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Helper\WorkflowTranslationHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Displays current workflow definitions.
 */
class DebugWorkflowDefinitionsCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'oro:debug:workflow:definitions';

    private const INLINE_DEPTH = 20;

    protected static array $tableHeader = [
        'System Name',
        'Label',
        'Related Entity',
        'Type',
        'Priority',
        'Applications',
        'Exclusive Active Group',
        'Exclusive Record Groups',
    ];

    private ManagerRegistry $doctrine;
    private TranslatorInterface $translator;

    public function __construct(ManagerRegistry $doctrine, TranslatorInterface $translator)
    {
        $this->doctrine = $doctrine;
        $this->translator = $translator;

        parent::__construct();
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function configure()
    {
        $this
            ->addArgument('workflow-name', InputArgument::OPTIONAL, 'Workflow name')
            ->setDescription('Displays current workflow definitions.')
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command displays workflow definitions
that are registered in the application.

  <info>php %command.full_name%</info>

Use <info>--workflow-name</info> option to display the definition of a specific workflow:

  <info>php %command.full_name% --workflow-name=<workflow-name></info>

HELP
            )
            ->addUsage('--workflow-name=<workflow-name>');
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->hasArgument('workflow-name') && $input->getArgument('workflow-name')) {
            return $this->dumpWorkflowDefinition($input->getArgument('workflow-name'), $output);
        }

        return $this->listWorkflowDefinitions($output);
    }

    protected function listWorkflowDefinitions(OutputInterface $output): int
    {
        /** @var WorkflowDefinition[] $workflows */
        $workflows = $this->getWorkflowDefinitionRepository()->findAll();
        if (\count($workflows)) {
            $table = new Table($output);
            $table->setHeaders(self::$tableHeader)->setRows([]);

            foreach ($workflows as $workflow) {
                $activeGroups = \implode(', ', $workflow->getExclusiveActiveGroups());
                if (!$activeGroups) {
                    $activeGroups = 'N/A';
                }

                $recordGroups = \implode(', ', $workflow->getExclusiveRecordGroups());
                if (!$recordGroups) {
                    $recordGroups = 'N/A';
                }

                $applications = \implode(', ', $workflow->getApplications());

                $row = [
                    $workflow->getName(),
                    $this->translator->trans($workflow->getLabel(), [], WorkflowTranslationHelper::TRANSLATION_DOMAIN),
                    $workflow->getRelatedEntity(),
                    $workflow->isSystem() ? 'System' : 'Custom',
                    (int)$workflow->getPriority(),
                    $applications,
                    $activeGroups,
                    $recordGroups,
                ];
                $table->addRow($row);
            }
            $table->render();

            return 0;
        }

        $output->writeln('No workflow definitions found.');

        return 1;
    }

    protected function dumpWorkflowDefinition($workflowName, OutputInterface $output): int
    {
        /** @var WorkflowDefinition $workflow */
        $workflow = $this->getWorkflowDefinitionRepository()->findOneBy(['name' => $workflowName]);

        if ($workflow) {
            $general = [
                'entity' => $workflow->getRelatedEntity(),
                'entity_attribute' => $workflow->getEntityAttributeName(),
                'steps_display_ordered' => $workflow->isStepsDisplayOrdered(),
                'priority' => $workflow->getPriority() ?: 0,
                'defaults' => ['active' => $workflow->isActive()],
                WorkflowConfiguration::NODE_APPLICATIONS => $workflow->getApplications(),
            ];

            $startStep = $workflow->getStartStep();
            if ($startStep) {
                $general['start_step'] = $startStep->getName();
            }

            if (count($exclusiveActiveGroups = $workflow->getExclusiveActiveGroups())) {
                $general['exclusive_active_groups'] = $exclusiveActiveGroups;
            }

            if (count($exclusiveRecordGroups = $workflow->getExclusiveRecordGroups())) {
                $general['exclusive_record_groups'] = $exclusiveRecordGroups;
            }

            $configuration = $workflow->getConfiguration();

            $this->clearConfiguration($configuration);

            $definition = ['workflows' => [$workflow->getName() => \array_merge($general, $configuration)]];

            $output->write(Yaml::dump($definition, self::INLINE_DEPTH), true);

            return 0;
        }

        $output->writeln('No workflow definitions found.');

        return 1;
    }

    /**
     * Clears parameters containing translation keys.
     * Clears init context parameters on the top level because they are redundant.
     */
    protected function clearConfiguration(array &$config): void
    {
        $this->removeTranslationKeys($config);

        // Keys that are no present in {@see WorkflowConfiguration}, but added
        // by Oro\Bundle\WorkflowBundle\Configuration\WorkflowDefinitionConfigurationBuilder::processInitContext.
        $initContextKeys = [
            WorkflowConfiguration::NODE_INIT_ENTITIES,
            WorkflowConfiguration::NODE_INIT_ROUTES,
            WorkflowConfiguration::NODE_INIT_DATAGRIDS,
        ];
        foreach ($initContextKeys as $keyToRemove) {
            unset($config[$keyToRemove]);
        }
    }

    private function removeTranslationKeys(array &$config): void
    {
        // The parameters containing translation keys must be removed because they are generated during workflow import.
        $sections = [
            WorkflowConfiguration::NODE_STEPS => ['label'],
            WorkflowConfiguration::NODE_ATTRIBUTES => ['label'],
            WorkflowConfiguration::NODE_TRANSITIONS => ['label', 'message', 'button_label', 'button_title'],
        ];

        foreach ($sections as $section => $keysToRemove) {
            foreach ($config[$section] as &$item) {
                foreach ($keysToRemove as $keyToRemove) {
                    unset($item[$keyToRemove]);
                }
            }
        }
        unset($item);

        if (isset($config[WorkflowConfiguration::NODE_VARIABLE_DEFINITIONS])) {
            $variableDefinitions =& $config[WorkflowConfiguration::NODE_VARIABLE_DEFINITIONS];
            foreach ($variableDefinitions[WorkflowConfiguration::NODE_VARIABLES] as &$item) {
                unset($item['label'], $item['options']['form_options']['tooltip']);
            }
            unset($item);
        }
    }

    protected function getWorkflowDefinitionRepository(): WorkflowDefinitionRepository
    {
        return $this->doctrine->getRepository(WorkflowDefinition::class);
    }
}
