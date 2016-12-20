<?php

namespace Oro\Bundle\FilterBundle\Tests\Functional\Fixtures\Filter;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter;
use Oro\Bundle\FilterBundle\Filter\FilterInterface;

interface FixtureInterface
{
    /**
     * Creates data in db which will be queried using the filter
     *
     * @param EntityManager $em
     */
    public function createData(EntityManager $em);

    /**
     * Creates datasource with query builder containing query to be filtered with the filter
     *
     * @param EntityManager $em
     *
     * @return OrmFilterDatasourceAdapter
     */
    public function createFilterDatasourceAdapter(EntityManager $em);

    /**
     * Submits filter data
     *
     * @param FilterInterface $filter
     */
    public function submitFilter(FilterInterface $filter);

    /**
     * Checks that filter works properly (correct data were retrieved from db)
     *
     * @param \PHPUnit_Framework_Assert $assertions
     * @param array $actualData
     */
    public function assert(\PHPUnit_Framework_Assert $assertions, array $actualData);
}
