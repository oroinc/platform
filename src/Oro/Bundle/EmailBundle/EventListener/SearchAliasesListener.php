<?php

namespace Oro\Bundle\EmailBundle\EventListener;

use Oro\Bundle\ActivityBundle\Event\SearchAliasesEvent;

class SearchAliasesListener
{
    const EMAIL_CLASS_NAME = 'Oro\Bundle\EmailBundle\Entity\Email';

    /**
     * Add search alias to correct search by EmailUser index for Email entity
     *
     * @param SearchAliasesEvent $event
     */
    public function addEmailAliasEvent(SearchAliasesEvent $event)
    {
        $aliases = $event->getAliases();
        if (in_array(self::EMAIL_CLASS_NAME, $event->getTargetClasses(), true)) {
            $aliases[] = 'oro_email';
            $event->setAliases($aliases);
        }
    }
}
