<?php

namespace Oro\Bundle\EmailBundle\EventListener;

use Oro\Bundle\ActivityBundle\Event\SearchAliasesEvent;
use Oro\Bundle\EmailBundle\Entity\Email;

/**
 * Adds search alias to correct search by EmailUser index for Email entity.
 */
class SearchAliasesListener
{
    public function addEmailAliasEvent(SearchAliasesEvent $event): void
    {
        $aliases = $event->getAliases();
        if (\in_array(Email::class, $event->getTargetClasses(), true)) {
            $aliases[] = 'oro_email';
            $event->setAliases($aliases);
        }
    }
}
