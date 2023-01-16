<?php

namespace Oro\Bundle\ImportExportBundle\ORM\Query;

use Doctrine\ORM\Query;
use Oro\Bundle\BatchBundle\ORM\Query\AbstractBufferedQueryResultIterator;
use Oro\Bundle\BatchBundle\ORM\Query\QueryCountCalculator;
use Oro\Bundle\BatchBundle\ORM\Query\ResultIterator\IdentifierIterationStrategy;
use Oro\Bundle\BatchBundle\ORM\Query\ResultIterator\IdentityIterationStrategyInterface;

/**
 * This iterator implements the same logic as the BufferedIdentityQueryResultIterator, except that the "page"
 * is loaded after the last element is retrieved.
 * This allows to clear the entity manager after the last element of page has been processed
 * without affecting the new 'page'.
 */
final class ExportBufferedIdentityQueryResultIterator extends AbstractBufferedQueryResultIterator
{
    private IdentityIterationStrategyInterface $iterationStrategy;
    private ?array $identifiers = null;
    private array $rows = [];
    private int $pageSize = 200;
    private ?int $maxResults = null;
    private ?int $totalCount = null;
    private int $page = -1;
    private int $offset = -1;
    private int $position = -1;
    private bool $load = true;

    public function key(): int
    {
        return $this->position;
    }

    public function next(): void
    {
        $this->offset++;
        if (!isset($this->rows[$this->offset])) {
            $this->load = true;

            return;
        }

        $this->current = $this->rows[$this->offset] ?? null;
    }

    public function current(): mixed
    {
        $this->load();

        return parent::current();
    }

    public function valid(): bool
    {
        $this->load();

        return parent::valid();
    }

    public function rewind(): void
    {
        $this->offset = $this->page = $this->position = -1;
        $this->current = null;
        $this->load = true;

        $this->loadIdentifiers();
        $this->getIterationStrategy()->initializeDataQuery($this->getQuery());
    }

    public function count(): int
    {
        if (null === $this->totalCount) {
            if ($this->useCountWalker || $this->maxResults) {
                $this->loadIdentifiers();
                $this->totalCount = count($this->identifiers);
            } else {
                $query = $this->cloneQuery($this->getQuery());
                $query->setMaxResults($this->maxResults);
                $this->totalCount = QueryCountCalculator::calculateCount($query, $this->useCountWalker);
            }
        }

        return $this->totalCount;
    }

    private function load(): void
    {
        if (null === $this->identifiers) {
            $this->rewind();
        }

        if (!$this->load) {
            return;
        }

        $this->offset = 0;
        $this->load = false;
        $this->loadNextPage();
        if ($this->rows) {
            $this->current = $this->rows[$this->offset];
            $this->position = $this->offset + $this->pageSize * $this->page;
        } else {
            $this->current = null;
        }
    }

    private function loadNextPage(): void
    {
        if ($this->pageCallback && $this->page !== -1) {
            call_user_func($this->pageCallback);
        }

        $this->page++;
        $identifiers = array_slice($this->identifiers, $this->pageSize * $this->page, $this->pageSize);
        $this->getIterationStrategy()->setDataQueryIdentifiers($this->getQuery(), $identifiers);

        try {
            $this->rows = $this->getQuery()->execute();
        } catch (\Exception $e) {
            $this->handleException($e);
        }

        if ($this->pageLoadedCallback) {
            $this->rows = call_user_func($this->pageLoadedCallback, $this->rows);
        }
    }

    protected function initializeQuery(Query $query): void
    {
        $this->maxResults = $query->getMaxResults();
        if ($this->maxResults && $this->maxResults < $this->pageSize) {
            $this->pageSize = $this->maxResults;
        }
    }

    private function loadIdentifiers(): void
    {
        if (null !== $this->identifiers) {
            return;
        }

        $query = $this->cloneQuery($this->getQuery());
        $this->getIterationStrategy()->initializeIdentityQuery($query);

        try {
            $this->identifiers = $query->execute();
        } catch (\Exception $e) {
            $this->handleException($e);
        }
    }

    public function setIterationStrategy(IdentityIterationStrategyInterface $iterationStrategy): self
    {
        $this->assertQueryWasNotExecuted('iteration strategy');
        $this->iterationStrategy = $iterationStrategy;

        return $this;
    }

    private function getIterationStrategy(): IdentityIterationStrategyInterface
    {
        return $this->iterationStrategy ?? new IdentifierIterationStrategy();
    }

    public function setBufferSize($bufferSize): self
    {
        parent::setBufferSize($bufferSize);
        $this->pageSize = (int)$bufferSize;

        return $this;
    }

    private function handleException(\Exception $e): void
    {
        $message = 'Can not autodetect row identifier, set custom iteration strategy to handle complex query.';
        throw new \LogicException($message, 0, $e);
    }
}
