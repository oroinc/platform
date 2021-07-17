<?php

namespace Oro\Bundle\WorkflowBundle\Form\EventListener;

use Oro\Bundle\FormBundle\Utils\FormUtils;
use Oro\Bundle\NotificationBundle\Entity\EmailNotification;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Event\WorkflowEvents;
use Oro\Bundle\WorkflowBundle\Form\Type\WorkflowDefinitionNotificationSelectType;
use Oro\Bundle\WorkflowBundle\Form\Type\WorkflowTransitionSelectType;
use Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Add workflow name and workflow transition name fields to the email notification form
 */
class EmailNotificationTypeListener
{
    /** @var WorkflowRegistry */
    protected $workflowRegistry;

    public function __construct(WorkflowRegistry $workflowRegistry)
    {
        $this->workflowRegistry = $workflowRegistry;
    }

    public function onPostSetData(FormEvent $event)
    {
        $data = $event->getData();
        if (!$data instanceof EmailNotification) {
            return;
        }

        $form = $event->getForm();

        $this->updateEventField($form, $data);
        $this->addWorkflowFields($form, $data);
    }

    public function onPreSubmit(FormEvent $event)
    {
        $data = $event->getData();

        if (isset($data['entityName'], $data['workflow_definition'])) {
            $form = $event->getForm();

            $this->addWorkflowField($form, $data['entityName']);
            $this->addTransitionNameField($form, $data['workflow_definition']);
        }
    }

    private function updateEventField(FormInterface $form, EmailNotification $data)
    {
        $entityName = $data->getEntityName();

        if (!$entityName || $this->workflowRegistry->hasWorkflowsByEntityClass($entityName)) {
            return;
        }

        if ($data->getEventName() === WorkflowEvents::NOTIFICATION_TRANSIT_EVENT) {
            $form->getData()->setEventName(null);
        }

        $choices = $form->get('eventName')->getConfig()->getOption('choices');
        unset($choices[WorkflowEvents::NOTIFICATION_TRANSIT_EVENT]);
        FormUtils::replaceField($form, 'eventName', ['choices' => $choices]);
    }

    private function addWorkflowFields(FormInterface $form, EmailNotification $data)
    {
        if (!$data->getEntityName() ||
            !$data->getEventName() ||
            $data->getEventName() !== WorkflowEvents::NOTIFICATION_TRANSIT_EVENT
        ) {
            return;
        }

        $this->addWorkflowField($form, $data->getEntityName());
        $this->addTransitionNameField($form, $data->getWorkflowDefinition());
        $this->updateTemplateField($form);
    }

    private function updateTemplateField(FormInterface $form)
    {
        $template = $form->get('template');

        $form->remove('template');
        $form->add($template);
    }

    /**
     * @param FormInterface $form
     * @param string $entityName
     */
    private function addWorkflowField(FormInterface $form, $entityName)
    {
        if ($form->has('workflow_definition')) {
            return;
        }

        $form->add(
            'workflow_definition',
            WorkflowDefinitionNotificationSelectType::class,
            [
                'label' => 'workflow',
                'required' => true,
                'placeholder' => '',
                'constraints' => [
                    new NotBlank()
                ],
                'configs' => [
                    'allowClear' => true,
                    'placeholder' => 'oro.workflow.form.choose_workflow'
                ],
                'attr' => [
                    'autocomplete' => 'off'
                ],
                'entityClass' => $entityName
            ]
        );
    }

    /**
     * @param FormInterface $form
     * @param string|WorkflowDefinition $workflow
     */
    private function addTransitionNameField(FormInterface $form, $workflow)
    {
        if ($form->has('workflow_transition_name')) {
            return;
        }

        $form->add(
            'workflow_transition_name',
            WorkflowTransitionSelectType::class,
            [
                'label' => 'transition',
                'required' => true,
                'placeholder' => '',
                'constraints' => [
                    new NotBlank()
                ],
                'configs' => [
                    'allowClear' => true,
                    'placeholder' => 'oro.workflow.form.choose_transition'
                ],
                'workflowName' => $workflow instanceof WorkflowDefinition ? $workflow->getName() : $workflow
            ]
        );
    }
}
