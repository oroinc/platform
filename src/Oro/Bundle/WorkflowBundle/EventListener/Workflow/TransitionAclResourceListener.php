<?php

namespace Oro\Bundle\WorkflowBundle\EventListener\Workflow;

use Oro\Bundle\WorkflowBundle\Event\Transition\PreAnnounceEvent;
use Oro\Component\Action\Condition\Configurable as ConfigurableCondition;
use Oro\Component\ConfigExpression\ExpressionFactory;

/**
 * Check ACL resource for a workflow transition.
 */
class TransitionAclResourceListener
{
    public function __construct(
        private ExpressionFactory $expressionFactory
    ) {
    }

    public function onPreAnnounce(PreAnnounceEvent $event): void
    {
        if (!$event->isAllowed()) {
            return;
        }

        $transition = $event->getTransition();
        $aclResource = $transition->getAclResource();
        if (!$aclResource) {
            return;
        }

        $aclDefinition = ['parameters' => $aclResource];
        $aclMessage = $transition->getAclMessage();
        if ($aclMessage) {
            $aclDefinition['message'] = $aclMessage;
        }

        // The $aclResource may contain workflow variable which are resolved by the ConfigurableCondition
        // That's why to check acl_granted we are calling it through ConfigurableCondition
        $expression = $this->expressionFactory->create(
            ConfigurableCondition::ALIAS,
            ['@acl_granted' => $aclDefinition]
        );

        $event->setAllowed(
            $expression->evaluate($event->getWorkflowItem(), $event->getErrors())
        );
    }
}
