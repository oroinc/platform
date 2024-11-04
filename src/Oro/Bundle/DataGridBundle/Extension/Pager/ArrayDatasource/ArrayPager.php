<?php

namespace Oro\Bundle\DataGridBundle\Extension\Pager\ArrayDatasource;

use Oro\Bundle\DataGridBundle\Datasource\ArrayDatasource\ArrayDatasource;
use Oro\Bundle\DataGridBundle\Extension\Pager\PagerInterface;

class ArrayPager implements PagerInterface
{
    /**
     * @var int
     */
    protected $maxPerPage = 0;

    /**
     * @var int
     */
    protected $page = 1;

    /**
     * @var int
     */
    protected $nbResults = 0;

    #[\Override]
    public function setMaxPerPage($maxPerPage)
    {
        $this->maxPerPage = $maxPerPage;
    }

    #[\Override]
    public function getMaxPerPage()
    {
        return $this->maxPerPage;
    }

    #[\Override]
    public function setPage($page)
    {
        $this->page = $page;
    }

    #[\Override]
    public function getPage()
    {
        return $this->page;
    }

    #[\Override]
    public function getPreviousPage()
    {
        return max($this->page - 1, $this->getFirstPage());
    }

    #[\Override]
    public function getNextPage()
    {
        return min($this->page + 1, $this->getLastPage());
    }

    #[\Override]
    public function getLastPage()
    {
        return ceil($this->nbResults / $this->getMaxPerPage());
    }

    #[\Override]
    public function getFirstPage()
    {
        return 1;
    }

    #[\Override]
    public function haveToPaginate()
    {
        return $this->maxPerPage && $this->nbResults > $this->getMaxPerPage();
    }

    #[\Override]
    public function getNbResults()
    {
        return $this->nbResults;
    }

    public function apply(ArrayDatasource $datasource)
    {
        $source = $datasource->getArraySource();
        $this->nbResults = count($source);
        $datasource->setArraySource(array_slice($source, $this->getOffset(), $this->maxPerPage));
    }

    /**
     * @return int
     */
    protected function getOffset()
    {
        return $this->page === 1 ? 0 : ($this->page - 1) * $this->maxPerPage;
    }
}
