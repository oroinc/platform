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

    /**
     * @param SearchQueryInterface $query
     */
    public function __construct(SearchQueryInterface $query)
    {
        $this->query = $query;
    }

    /**
     * {@inheritdoc}
     */
    public function getSource()
    {
        return $this->query;
    }

    /**
     * {@inheritdoc}
     */
    public function setBufferSize($size)
    {
        if ($size <= 0) {
            throw new \InvalidArgumentException('$bufferSize must be greater than 0');
        }

        $this->bufferSize = (int)$size;
    }

    /**
     * {@inheritDoc}
     */
    public function rewind()
    {
        $this->offset = -1;
        $this->page = -1;
        $this->position = -1;
        $this->current = null;

        $this->next();
    }

    /**
     * {@inheritdoc}
     */
    public function current()
    {
        return $this->current;
    }

    /**
     * {@inheritdoc}
     */
    public function key()
    {
        return $this->position;
    }

    /**
     * {@inheritdoc}
     */
    public function next()
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

    /**
     * {@inheritdoc}
     */
    public function valid()
    {
        return null !== $this->current;
    }

    /**
     * {@inheritdoc}
     */
    public function count()
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
