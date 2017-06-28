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

    /**
     * {@inheritdoc}
     */
    public function setMaxPerPage($maxPerPage)
    {
        $this->maxPerPage = $maxPerPage;
    }

    /**
     * {@inheritdoc}
     */
    public function getMaxPerPage()
    {
        return $this->maxPerPage;
    }

    /**
     * {@inheritdoc}
     */
    public function setPage($page)
    {
        $this->page = $page;
    }

    /**
     * {@inheritdoc}
     */
    public function getPage()
    {
        return $this->page;
    }

    /**
     * {@inheritdoc}
     */
    public function getPreviousPage()
    {
        return max($this->page - 1, $this->getFirstPage());
    }

    /**
     * {@inheritdoc}
     */
    public function getNextPage()
    {
        return min($this->page + 1, $this->getLastPage());
    }

    /**
     * {@inheritdoc}
     */
    public function getLastPage()
    {
        return ceil($this->nbResults / $this->getMaxPerPage());
    }

    /**
     * {@inheritdoc}
     */
    public function getFirstPage()
    {
        return 1;
    }

    /**
     * {@inheritdoc}
     */
    public function haveToPaginate()
    {
        return $this->maxPerPage && $this->nbResults > $this->getMaxPerPage();
    }

    /**
     * {@inheritdoc}
     */
    public function getNbResults()
    {
        return $this->nbResults;
    }

    /**
     * @param ArrayDatasource $datasource
     */
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
