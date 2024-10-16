<?php

namespace Oro\Bundle\SearchBundle\Datagrid\Datasource;

use Oro\Bundle\DataGridBundle\Datasource\Orm\IterableResultInterface;
use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;

/**
 * This class iterates over SearchQueryInterface.
 */
class SearchIterableResult implements IterableResultInterface
{
    /**
     * @var int
     */
    const DEFAULT_BUFFER_SIZE = 2000;

    /**
     * @var SearchQueryInterface
     */
    private $query;

    /**
     * @var int
     */
    private $pageSize = self::DEFAULT_BUFFER_SIZE;

    /**
     * @var int
     */
    private $offset = -1;

    /**
     * @var int
     */
    private $page = -1;

    /**
     * @var int
     */
    private $position = -1;

    /**
     * @var mixed|null
     */
    private $current;

    /**
     * @var int
     */
    private $totalCount;

    /**
     * @var array
     */
    private $rows;

    public function __construct(SearchQueryInterface $query)
    {
        $this->query = $query;
    }

    #[\Override]
    public function getSource()
    {
        return $this->query;
    }

    #[\Override]
    public function setBufferSize($size)
    {
        if ($size <= 0) {
            throw new \InvalidArgumentException('$bufferSize must be greater than 0');
        }

        $this->pageSize = (int)$size;
    }

    #[\Override]
    public function rewind(): void
    {
        $this->offset = -1;
        $this->page = -1;
        $this->position = -1;
        $this->current = null;

        $this->next();
    }

    #[\Override]
    public function current(): mixed
    {
        return $this->current;
    }

    #[\Override]
    public function key(): int
    {
        return $this->position;
    }

    #[\Override]
    public function next(): void
    {
        $this->offset++;

        if (!isset($this->rows[$this->offset])) {
            $this->offset = 0;
            $this->loadNextPage();
        }

        if ($this->rows) {
            $this->current = $this->rows[$this->offset];
            $this->position = $this->offset + $this->pageSize * $this->page;
        } else {
            $this->current = null;
        }
    }

    /**
     * Loads next page
     */
    protected function loadNextPage()
    {
        $this->page++;
        $query = clone $this->query;
        $query->setMaxResults($this->pageSize);
        $query->setFirstResult($this->pageSize * $this->page);

        $this->rows = array_values($query->execute());
    }

    #[\Override]
    public function valid(): bool
    {
        return null !== $this->current;
    }

    public function count(): int
    {
        if (null === $this->totalCount) {
            $query = clone $this->query;
            $query->setMaxResults(null);
            $query->setFirstResult(null);

            $this->totalCount = $query->getTotalCount();
        }

        return $this->totalCount;
    }
}
