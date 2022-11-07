<?php

namespace Oro\Bundle\FilterBundle\Tests\Functional\Filter;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter;
use Oro\Bundle\FilterBundle\Filter\DuplicateFilter;
use Oro\Bundle\FilterBundle\Form\Type\Filter\BooleanFilterType;
use Oro\Bundle\FilterBundle\Tests\Functional\Fixtures\LoadDuplicateUserData;
use Oro\Bundle\FilterBundle\Tests\Functional\Fixtures\LoadDuplicateUserRelationData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * @dbIsolationPerTest
 */
class DuplicateFilterTest extends WebTestCase
{
    private DuplicateFilter $filter;

    protected function setUp(): void
    {
        $this->initClient();
        $this->filter = self::getContainer()->get('oro_filter.duplicate_filter');
    }

    private function createQueryBuilder(string $alias): QueryBuilder
    {
        $doctrine = self::getContainer()->get('doctrine');

        return $doctrine->getRepository(User::class)->createQueryBuilder($alias);
    }

    /**
     * @dataProvider duplicateDataProvider
     */
    public function testDuplicateField(int $filterType, array $expected)
    {
        $this->loadFixtures([LoadDuplicateUserData::class]);

        $qb = $this->createQueryBuilder('u');
        $qb->select('u.username')
            ->orderBy('u.username')
            ->andWhere($qb->expr()->in('u.username', ['u1', 'u2', 'u3']));

        $this->filter->init('duplicate', ['data_name' => 'u.firstName']);

        $filterForm = $this->filter->getForm();
        $filterForm->submit(['value' => $filterType]);
        self::assertTrue($filterForm->isValid());
        self::assertTrue($filterForm->isSynchronized());

        $ds = new OrmFilterDatasourceAdapter($qb);
        $this->filter->apply($ds, $filterForm->getData());

        $qb = $ds->getQueryBuilder();

        $actualData = $qb->getQuery()->getResult();
        self::assertEquals($expected, array_map('current', $actualData));

        $dql = $qb->getDQL();
        self::assertStringNotContainsString('EXISTS(', $dql);
        self::assertStringContainsString('GROUP BY ', $dql);
    }

    /**
     * @dataProvider duplicateDataProvider
     */
    public function testDuplicateRelation(int $filterType, array $expected)
    {
        $this->loadFixtures([LoadDuplicateUserRelationData::class]);

        $qb = $this->createQueryBuilder('u');
        $qb->select('u.username')
            ->join('u.emails', 'e')
            ->orderBy('u.username')
            ->andWhere($qb->expr()->in('u.username', ['u1', 'u2', 'u3']));

        $this->filter->init('duplicate', ['data_name' => 'e.email']);

        $filterForm = $this->filter->getForm();
        $filterForm->submit(['value' => $filterType]);
        self::assertTrue($filterForm->isValid());
        self::assertTrue($filterForm->isSynchronized());

        $ds = new OrmFilterDatasourceAdapter($qb);
        $this->filter->apply($ds, $filterForm->getData());

        $qb = $ds->getQueryBuilder();

        $actualData = $qb->getQuery()->getResult();
        self::assertEquals($expected, array_map('current', $actualData));
        $dql = $qb->getDQL();
        self::assertStringContainsString('EXISTS(', $dql);
        self::assertStringContainsString('GROUP BY ', $dql);
    }

    public function duplicateDataProvider(): array
    {
        return [
            'has duplicate'    => [
                'filterType' => BooleanFilterType::TYPE_YES,
                'expected'   => ['u1', 'u2']
            ],
            'has no duplicate' => [
                'filterType' => BooleanFilterType::TYPE_NO,
                'expected'   => ['u3']
            ]
        ];
    }
}
