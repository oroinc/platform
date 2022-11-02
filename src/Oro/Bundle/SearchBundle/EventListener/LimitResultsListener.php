<?php

namespace Oro\Bundle\SearchBundle\EventListener;

use Oro\Bundle\SearchBundle\Event\SearchQueryAwareEventInterface;

/**
 * Sets hard limit on size for queries to search index.
 */
class LimitResultsListener
{
    public const RESULTS_LIMIT = 1000;

    public function onBeforeSearch(SearchQueryAwareEventInterface $event): void
    {
        $query = $event->getQuery();

        $maxResults = $query->getCriteria()->getMaxResults();

        if (!$maxResults || $maxResults > self::RESULTS_LIMIT) {
            $query->getCriteria()->setMaxResults(self::RESULTS_LIMIT);
        }
    }
}
