<?php

namespace Oro\Bundle\WorkflowBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Yaml\Yaml;

use Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowDefinitionRepository;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Helper\WorkflowTranslationHelper;

class DebugWorkflowDefinitionsCommand extends ContainerAwareCommand
{
    const NAME = 'oro:debug:workflow:definitions';
    const INLINE_DEPTH = 20;

    /** @var array */
    protected static $tableHeader = [
        'System Name',
        'Label',
        'Related Entity',
        'Type',
        'Priority',
        'Exclusive Active Group',
        'Exclusive Record Groups'
    ];

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName(static::NAME)
            ->setDescription('List workflow definitions registered within application')
            ->addArgument(
                'workflow-name',
                InputArgument::OPTIONAL,
                'Name of the workflow definition that should be dumped'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->hasArgument('workflow-name') && $input->getArgument('workflow-name')) {
            return $this->dumpWorkflowDefinition($input->getArgument('workflow-name'), $output);
        } else {
            return $this->listWorkflowDefinitions($output);
        }
    }

    /**
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function listWorkflowDefinitions(OutputInterface $output)
    {
        /** @var TranslatorInterface $translator */
        $translator = $this->getContainer()->get('translator');

        /** @var WorkflowDefinition[] $workflows */
        $workflows = $this->getWorkflowDefinitionRepository()->findAll();
        if (count($workflows)) {
            $table = new Table($output);
            $table->setHeaders(self::$tableHeader)->setRows([]);

            foreach ($workflows as $workflow) {
                $activeGroups = implode(', ', $workflow->getExclusiveActiveGroups());
                if (!$activeGroups) {
                    $activeGroups = 'N/A';
                }

                $recordGroups = implode(', ', $workflow->getExclusiveRecordGroups());
                if (!$recordGroups) {
                    $recordGroups = 'N/A';
                }

                $row = [
                    $workflow->getName(),
                    $translator->trans($workflow->getLabel(), [], WorkflowTranslationHelper::TRANSLATION_DOMAIN),
                    $workflow->getRelatedEntity(),
                    $workflow->isSystem() ? 'System' : 'Custom',
                    (int)$workflow->getPriority(),
                    $activeGroups,
                    $recordGroups
                ];
                $table->addRow($row);
            }
            $table->render();

            return 0;
        } else {
            $output->writeln('No workflow definitions found.');

            return 1;
        }
    }

    /**
     * @param string $workflowName
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function dumpWorkflowDefinition($workflowName, OutputInterface $output)
    {
        /** @var WorkflowDefinition $workflow */
        $workflow = $this->getWorkflowDefinitionRepository()->findOneBy(['name' => $workflowName]);

        if ($workflow) {
            $general = [
                'entity' => $workflow->getRelatedEntity(),
                'entity_attribute' => $workflow->getEntityAttributeName(),
                'steps_display_ordered' => $workflow->isStepsDisplayOrdered(),
                'priority' => $workflow->getPriority() ?: 0,
                'defaults' => [
                    'active' => $workflow->isActive()
                ],
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

            $definition = [
                'workflows' => [
                    $workflow->getName() => array_merge($general, $configuration)
                ]
            ];

            $output->write(Yaml::dump($definition, self::INLINE_DEPTH), true);

            return 0;
        } else {
            $output->writeln('No workflow definitions found.');

            return 1;
        }
    }

    /**
     * Clear "label" and "message" options from configuration
     *
     * @param $array
     */
    protected function clearConfiguration(&$array)
    {
        foreach ($array as $key => &$value) {
            if (is_array($value)) {
                $countBefore = count($value);
                $this->clearConfiguration($value);
                if (empty($value) && $countBefore) {
                    $array[$key] = null;
                }
            }
            if (in_array(strtolower($key), ['label', 'message'], true)) {
                unset($array[$key]);
            }
        }
    }

    /**
     * @return WorkflowDefinitionRepository
     */
    protected function getWorkflowDefinitionRepository()
    {
        return $this->getContainer()->get('doctrine')->getRepository(WorkflowDefinition::class);
    }
}
