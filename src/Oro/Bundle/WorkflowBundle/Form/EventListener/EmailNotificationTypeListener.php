<?php

namespace Oro\Bundle\WorkflowBundle\Form\EventListener;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\NotificationBundle\Entity\EmailNotification;
use Oro\Bundle\NotificationBundle\Entity\Event;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Form\Type\WorkflowDefinitionNotificationSelectType;
use Oro\Bundle\WorkflowBundle\Form\Type\WorkflowTransitionSelectType;
use Oro\Bundle\WorkflowBundle\Migrations\Data\ORM\LoadWorkflowNotificationEvents;
use Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class EmailNotificationTypeListener
{
    /** @var WorkflowRegistry */
    protected $workflowRegistry;

    /**
     * @param WorkflowRegistry $workflowRegistry
     */
    public function __construct(WorkflowRegistry $workflowRegistry)
    {
        $this->workflowRegistry = $workflowRegistry;
    }

    /**
     * @param FormEvent $event
     */
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

    /**
     * @param FormEvent $event
     */
    public function onPreSubmit(FormEvent $event)
    {
        $data = $event->getData();

        if (isset($data['entityName'], $data['workflow_definition'])) {
            $form = $event->getForm();

            $this->addWorkflowField($form, $data['entityName']);
            $this->addTransitionNameField($form, $data['workflow_definition']);
        }
    }

    /**
     * @param FormInterface $form
     * @param EmailNotification $data
     */
    private function updateEventField(FormInterface $form, EmailNotification $data)
    {
        $entityName = $data->getEntityName();

        if (!$entityName || $this->workflowRegistry->hasWorkflowsByEntityClass($entityName)) {
            return;
        }

        $event = $data->getEvent();
        if ($event instanceof Event && $event->getName() === LoadWorkflowNotificationEvents::TRANSIT_EVENT) {
            $form->getData()->setEvent(null);
        }

        /** @var QueryBuilder $qb */
        $qb = $form->get('event')->getConfig()->getOption('query_builder');
        $qb->andWhere($qb->expr()->neq('c.name', ':event'))
            ->setParameter('event', LoadWorkflowNotificationEvents::TRANSIT_EVENT);
    }

    /**
     * @param FormInterface $form
     * @param EmailNotification $data
     */
    private function addWorkflowFields(FormInterface $form, EmailNotification $data)
    {
        if (!$data->getEntityName() ||
            !$data->getEvent() ||
            $data->getEvent()->getName() !== LoadWorkflowNotificationEvents::TRANSIT_EVENT
        ) {
            return;
        }

        $this->addWorkflowField($form, $data->getEntityName());
        $this->addTransitionNameField($form, $data->getWorkflowDefinition());
        $this->updateTemplateField($form);
    }

    /**
     * @param FormInterface $form
     */
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
