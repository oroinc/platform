<?php

namespace Oro\Bundle\WorkflowBundle\Form\EventListener;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Component\Action\Action\ActionInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvents;

class FormInitListener implements EventSubscriberInterface
{
    /**
     * @var WorkflowItem
     */
    protected $workflowItem;

    /**
     * @var ActionInterface
     */
    protected $initAction;

    /**
     * Initialize listener with required data
     */
    public function initialize(WorkflowItem $workflowItem, ActionInterface $initAction)
    {
        $this->workflowItem = $workflowItem;
        $this->initAction = $initAction;
    }

    /**
     * Executes init actions
     */
    public function executeInitAction()
    {
        $this->initAction->execute($this->workflowItem);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return array(FormEvents::PRE_SET_DATA => 'executeInitAction');
    }
}
