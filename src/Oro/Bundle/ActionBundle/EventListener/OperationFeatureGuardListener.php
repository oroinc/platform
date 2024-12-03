<?php

namespace Oro\Bundle\ActionBundle\EventListener;

use Oro\Bundle\ActionBundle\Event\OperationAnnounceEvent;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;

/**
 * Check operation feature allowance on pre_announce
 */
class OperationFeatureGuardListener
{
    public function __construct(
        private FeatureChecker $featureChecker
    ) {
    }

    public function checkFeature(OperationAnnounceEvent $event): void
    {
        if (!$event->isAllowed()) {
            return;
        }

        $isResourceEnabled = $this->featureChecker->isResourceEnabled(
            $event->getOperationDefinition()->getName(),
            'operations'
        );

        $event->setAllowed($isResourceEnabled);
    }
}
