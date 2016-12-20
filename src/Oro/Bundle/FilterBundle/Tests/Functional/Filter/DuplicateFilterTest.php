<?php

namespace Oro\Bundle\FilterBundle\Tests\Functional\Filter;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\FilterBundle\Filter\FilterInterface;
use Oro\Bundle\FilterBundle\Tests\Functional\Fixtures\Filter\FixtureInterface;
use Oro\Bundle\FilterBundle\Tests\Functional\Fixtures\Filter\HasDuplicateField;
use Oro\Bundle\FilterBundle\Tests\Functional\Fixtures\Filter\HasDuplicateRelation;
use Oro\Bundle\FilterBundle\Tests\Functional\Fixtures\Filter\HasNotDuplicateField;
use Oro\Bundle\FilterBundle\Tests\Functional\Fixtures\Filter\HasNotDuplicateRelation;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * @dbIsolationPerTest
 */
class DuplicateFilterTest extends WebTestCase
{
    public function setUp()
    {
        $this->initClient();
    }

    /**
     * @dataProvider casesProvider
     */
    public function testCases(FixtureInterface $fixture)
    {
        $em = $this->getEntityManager();
        $fixture->createData($em);
        $em->flush();

        $ds = $fixture->createFilterDatasourceAdapter($em);
        $filter = $this->getDuplicateFilter();
        $filterForm = $filter->getForm();
        $fixture->submitFilter($filter);
        $this->assertTrue($filterForm->isValid());

        $qb = $ds->getQueryBuilder();
        $filter->apply($ds, $filterForm->getData());
        $fixture->assert($this, $qb->getQuery()->getResult());
    }

    public function casesProvider()
    {
        return [
            [new HasDuplicateField()],
            [new HasNotDuplicateField()],
            [new HasDuplicateRelation()],
            [new HasNotDuplicateRelation()],
        ];
    }

    /**
     * @return FilterInterface
     */
    protected function getDuplicateFilter()
    {
        return $this->getContainer()->get('oro_filter.duplicate_filter');
    }

    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        return $this->getContainer()->get('doctrine')
            ->getManagerForClass(User::class);
    }
}
