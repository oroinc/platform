<?php

namespace Oro\Bundle\DataGridBundle\Extension\Pager;

interface PagerInterface
{
    /**
     * Pager parameters
     */
    const PAGER_ROOT_PARAM = '_pager';
    const PAGE_PARAM       = '_page';
    const PER_PAGE_PARAM   = '_per_page';
    const DISABLED_PARAM   = '_disabled';

    const MINIFIED_PAGE_PARAM     = 'i';
    const MINIFIED_PER_PAGE_PARAM = 'p';

    /**
     * @deprecated Since 1.12, will be removed after 1.14.
     * @see \Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject::TOTAL_RECORDS_PATH
     * @see \Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject::getTotalRecords
     * @see \Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject::setTotalRecords
     */
    const TOTAL_PATH_PARAM = '[options][totalRecords]';

    /**
     * Initialize the pager.
     *
     * @return void
     */
    public function init();

    /**
     * Set max records per page
     *
     * @param  int $maxPerPage
     *
     * @return void
     */
    public function setMaxPerPage($maxPerPage);

    /**
     * Get max records per page
     *
     * @return int
     */
    public function getMaxPerPage();

    /**
     * Set current page
     *
     * @param  int $page
     *
     * @return void
     */
    public function setPage($page);

    /**
     * Get current page
     *
     * @return int
     */
    public function getPage();

    /**
     * Returns the previous page.
     *
     * @return int
     */
    public function getPreviousPage();

    /**
     * Returns the next page.
     *
     * @return integer
     */
    public function getNextPage();

    /**
     * Returns the last page number.
     *
     * @return integer
     */
    public function getLastPage();

    /**
     * Returns the first page number.
     *
     * @return integer
     */
    public function getFirstPage();

    /**
     * @return boolean
     */
    public function haveToPaginate();

    /**
     * Returns the number of results.
     *
     * @return integer
     */
    public function getNbResults();
}
