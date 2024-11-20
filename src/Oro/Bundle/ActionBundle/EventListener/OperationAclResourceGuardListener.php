<?php

namespace Oro\Bundle\ActionBundle\EventListener;

use Oro\Bundle\ActionBundle\Event\OperationAnnounceEvent;
use Oro\Bundle\ActionBundle\Model\ActionExecutor;
use Oro\Bundle\ActionBundle\Resolver\OptionsResolver;

/**
 * Check operation configured acl_resource on pre_announce
 */
class OperationAclResourceGuardListener
{
    public function __construct(
        private ActionExecutor $actionExecutor,
        private OptionsResolver $optionsResolver
    ) {
    }

    public function checkAcl(OperationAnnounceEvent $event): void
    {
        if (!$event->isAllowed()) {
            return;
        }

        $aclResource = $event->getOperationDefinition()->getAclResource();
        if (!$aclResource) {
            return;
        }

        if (!is_array($aclResource)) {
            $aclResource = [$aclResource];
        }

        $isGranted = $this->actionExecutor->evaluateExpression(
            'acl_granted',
            $this->optionsResolver->resolveOptions($event->getActionData(), $aclResource),
            $event->getErrors()
        );

        $event->setAllowed($isGranted);
    }
}
