<?php

namespace Oro\Bundle\WorkflowBundle\Command;

use Oro\Bundle\WorkflowBundle\Configuration\Handler\FilterHandler;
use Oro\Bundle\WorkflowBundle\Helper\WorkflowTranslationHelper;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\Translation\TranslatorInterface;

use Symfony\Component\Yaml\Yaml;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;

class DebugWorkflowDefinitionsCommand extends ContainerAwareCommand
{
    const NAME = 'oro:debug:workflow:definitions';
    const INLINE_DEPTH = 6;

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
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function listWorkflowDefinitions(OutputInterface $output)
    {
        /** @var TranslatorInterface $translator */
        $translator = $this->getContainer()->get('translator');

        $workflows = $this->getContainer()->get('doctrine')
            ->getRepository('Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition')
            ->findAll();
        if (count($workflows)) {
            $table = new Table($output);
            $table->setHeaders([
                'System Name',
                'Label',
                'Related Entity',
                'Type',
                'Priority',
                'Exclusive Active Group',
                'Exclusive Record Groups',
            ])
                ->setRows([]);
            /** @var WorkflowDefinition $workflow */
            foreach ($workflows as $workflow) {
                $row = [
                    $workflow->getName(),
                    $translator->trans($workflow->getLabel(), [], WorkflowTranslationHelper::TRANSLATION_DOMAIN),
                    $workflow->getRelatedEntity(),
                    $workflow->isSystem() ? 'System' : 'Custom',
                    $workflow->getPriority() ?: 0,
                    implode(', ', $workflow->getExclusiveActiveGroups()) ?: 'N/A',
                    implode(', ', $workflow->getExclusiveRecordGroups()) ?: 'N/A',
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
     * @param $workflowName
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function dumpWorkflowDefinition($workflowName, OutputInterface $output)
    {
        /** @var WorkflowDefinition $workflow */
        $workflow = $this->getContainer()
            ->get('doctrine')
            ->getRepository('Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition')
            ->findOneBy(['name' => $workflowName]);

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

            if ($startStep = $workflow->getStartStep()) {
                $general['start_step'] = $startStep->getName();
            }

            if (count($exclusiveActiveGroups = $workflow->getExclusiveActiveGroups())) {
                $general['exclusive_active_groups'] = $exclusiveActiveGroups;
            }

            if (count($exclusiveRecordGroups = $workflow->getExclusiveRecordGroups())) {
                $general['exclusive_record_groups'] = $exclusiveRecordGroups;
            }

            $configuration = $workflow->getConfiguration();

            //Closure to clear "label" and "message" options from configuration
            $callback = function (&$array) use (&$callback) {
                foreach ($array as $key => &$value) {
                    if (is_array($value)) {
                        $countBefore = count($value);
                        $callback($value);
                        if (empty($value) && $countBefore) {
                            $array[$key] = null;
                        }
                    }
                    if (in_array(strtolower($key), ['label', 'message'], true)) {
                        unset($array[$key]);
                    }
                }
            };

            $callback($configuration);

            $definition = [
                'workflows' => [
                    $workflow->getName() =>
                        array_merge(
                            $general,
                            $configuration
                        )
                ]
            ];

            $output->write(Yaml::dump($definition, self::INLINE_DEPTH), true);

            return 0;
        } else {
            $output->writeln('No workflow definitions found.');

            return 1;
        }
    }
}
