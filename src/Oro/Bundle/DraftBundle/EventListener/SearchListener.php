<?php

namespace Oro\Bundle\DraftBundle\EventListener;

use Oro\Bundle\DraftBundle\Entity\DraftableInterface;
use Oro\Bundle\DraftBundle\Helper\DraftHelper;
use Oro\Bundle\SearchBundle\Event\PrepareEntityMapEvent;

/**
 * Restrict indexation of drafts
 */
class SearchListener
{
    /**
     * Clear PrepareEntityMapEvent data to skip indexation of the entity
     */
    public function prepareEntityMapEvent(PrepareEntityMapEvent $event): void
    {
        $entity = $event->getEntity();
        if ($entity instanceof DraftableInterface && DraftHelper::isDraft($entity)) {
            $event->setData([]);
        }
    }
}
