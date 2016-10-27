<?php

namespace Oro\Bundle\EmailBundle\EventListener;

use Oro\Bundle\ActivityBundle\Event\SearchAliasesEvent;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureToggleableInterface;

class SearchAliasesListener implements FeatureToggleableInterface
{
    use FeatureCheckerHolderTrait;

    /**
     * Add search alias to correct search by EmailUser index for Email entity
     *
     * @param SearchAliasesEvent $event
     */
    public function addEmailAliasEvent(SearchAliasesEvent $event)
    {
        if (!$this->isFeaturesEnabled()) {
            return;
        }

        $aliases = $event->getAliases();
        if (in_array(Email::ENTITY_CLASS, $event->getTargetClasses(), true)) {
            $aliases[] = 'oro_email';
            $event->setAliases($aliases);
        }
    }
}
