<?php

namespace Oro\Bundle\BatchBundle\ORM\Query;

/**
 * Iterates results of Query using IterableResult, allows to iterate large query results without risk of getting
 * out of memory error. Uses less memory than BufferedQueryResultIterator because of
 * using \Doctrine\ORM\Internal\Hydration\IterableResult with row-by-row hydration.
 *
 * This class has problems when iterating through changing dataset.
 * Use BufferedIdentityQueryResultIterator instead.
 *
 * It is not recommended to use pageLoadedCallback in this class as it eliminates the profit of using IterableResult.
 */
class BufferedQueryWithDoctrineIterableResultIterator extends BufferedQueryResultIterator
{
    /**
     * @var \Iterable
     */
    private $innerIterator;

    /**
     * {@inheritDoc}
     */
    public function next(): void
    {
        $this->current = null;
        $this->offset++;

        if ($this->innerIterator) {
            $this->innerIterator->next();
        }

        if ((!$this->innerIterator || !$this->innerIterator->current()) && !$this->loadNextPage()) {
            return;
        }

        $currentKey = $this->innerIterator->key();
        if ($currentKey !== null) {
            $this->current = $this->innerIterator->current()[$currentKey];
            $this->position = $this->offset + $this->getQuery()->getMaxResults() * $this->page;
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function loadNextPage(): bool
    {
        if ($this->pageCallback && $this->page !== -1) {
            call_user_func($this->pageCallback);
        }

        $query = $this->getQuery();

        if (!$this->calculateNextPage($query)) {
            return false;
        }

        $this->prepareQueryToExecute($query);

        $this->innerIterator = $query->iterate([], $this->requestedHydrationMode);

        // It is not recommended to use pageLoadedCallback in this class as it eliminates the profit of using
        // IterableResult. The following bypass is implemented for the compatability with interface.
        if ($this->pageLoadedCallback) {
            $rows = call_user_func($this->pageLoadedCallback, \iterator_to_array($this->innerIterator, false));
            $this->innerIterator = new \ArrayIterator($rows);
        } else {
            $this->innerIterator->next();
        }

        return true;
    }
}
