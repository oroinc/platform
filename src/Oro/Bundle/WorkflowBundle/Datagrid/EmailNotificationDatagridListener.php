<?php

namespace Oro\Bundle\WorkflowBundle\Datagrid;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\TranslationBundle\Translation\KeySource\TranslationKeySource;
use Oro\Bundle\TranslationBundle\Translation\TranslationKeyGenerator;
use Oro\Bundle\TranslationBundle\Translation\TranslationKeyTemplateInterface;
use Oro\Bundle\WorkflowBundle\Helper\WorkflowTranslationHelper;
use Oro\Bundle\WorkflowBundle\Translation\KeyTemplate\TransitionLabelTemplate;
use Oro\Bundle\WorkflowBundle\Translation\KeyTemplate\WorkflowLabelTemplate;
use Symfony\Component\Translation\TranslatorInterface;

class EmailNotificationDatagridListener
{
    /** @var TranslatorInterface */
    protected $translator;

    /** @var TranslationKeyGenerator */
    protected $generator;

    /**
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @param BuildBefore $event
     */
    public function onBuildBefore(BuildBefore $event)
    {
        $config = $event->getConfig();
        $config->offsetSetByPath(sprintf('[filters][columns][%s][type]', 'workflow_definition'), 'workflow');
    }

    /**
     * @param OrmResultAfter $event
     */
    public function onResultAfter(OrmResultAfter $event)
    {
        /** @var ResultRecord[] $records */
        $records = $event->getRecords();
        foreach ($records as $record) {
            $workflow = $record->getValue('workflow_definition_target_field');
            if (!$workflow) {
                continue;
            }

            $record->setValue('workflow_definition_target_field', $this->translateWorkflowName($workflow));

            $transitionName = $record->getValue('workflow_transition_name');
            if (!$transitionName) {
                continue;
            }

            $record->setValue('workflow_transition_name', $this->translateTransitionName($workflow, $transitionName));
        }
    }

    /**
     * @param string $name
     *
     * @return string
     */
    private function translateWorkflowName($name)
    {
        return $this->translate(new WorkflowLabelTemplate(), ['workflow_name' => $name]);
    }

    /**
     * @param string $workflowName
     * @param string $transitionName
     *
     * @return string
     */
    private function translateTransitionName($workflowName, $transitionName)
    {
        $name = $this->translate(
            new TransitionLabelTemplate(),
            ['workflow_name' => $workflowName, 'transition_name' => $transitionName]
        );

        return sprintf('%s (%s)', $name, $transitionName);
    }

    /**
     * @param TranslationKeyTemplateInterface $template
     * @param array $data
     *
     * @return string
     */
    private function translate(TranslationKeyTemplateInterface $template, array $data)
    {
        if (!$this->generator) {
            $this->generator = new TranslationKeyGenerator();
        }

        $key = $this->generator->generate(new TranslationKeySource($template, $data));

        return $this->translator->trans($key, [], WorkflowTranslationHelper::TRANSLATION_DOMAIN);
    }
}
