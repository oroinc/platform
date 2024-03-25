<?php

namespace Oro\Bundle\WorkflowBundle\EventListener\Workflow;

use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfiguration;
use Oro\Bundle\WorkflowBundle\Event\Transition\TransitionEvent;
use Oro\Component\Action\Action\ActionFactoryInterface;

/**
 * Executes resolve_destination_page for transitions with display type set to "page".
 */
class ResolveDestinationPageListener
{
    public function __construct(
        private ActionFactoryInterface $actionFactory
    ) {
    }

    public function onTransition(TransitionEvent $event): void
    {
        $transition = $event->getTransition();
        if ($transition->getDisplayType() !== WorkflowConfiguration::TRANSITION_DISPLAY_TYPE_PAGE) {
            return;
        }

        $action = $this->actionFactory->create(
            'resolve_destination_page',
            ['destination' => $transition->getDestinationPage()]
        );
        $action->execute($event->getWorkflowItem());
    }
}
