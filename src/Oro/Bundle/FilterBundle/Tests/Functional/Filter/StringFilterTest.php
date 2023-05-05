<?php

namespace Oro\Bundle\FilterBundle\Tests\Functional\Filter;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\FilterBundle\Filter\StringFilter;
use Oro\Bundle\FilterBundle\Form\Type\Filter\TextFilterType;
use Oro\Bundle\FilterBundle\Tests\Functional\Fixtures\LoadUserData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * @dbIsolationPerTest
 */
class StringFilterTest extends WebTestCase
{
    private StringFilter $filter;

    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadUserData::class]);
        $this->filter = self::getContainer()->get('oro_filter.string_filter');
    }

    private function createQueryBuilder(string $alias): QueryBuilder
    {
        $doctrine = self::getContainer()->get('doctrine');

        return $doctrine->getRepository(User::class)->createQueryBuilder($alias);
    }

    /**
     * @dataProvider filterProvider
     */
    public function testFilterWithForm(string $filterName, array $filterData, array $expectedResult)
    {
        $qb = $this->createQueryBuilder('u');
        $qb->select('u.username')
            ->orderBy('u.username')
            ->andWhere($qb->expr()->in('u.username', ['u1', 'u2', 'u3']));

        $this->filter->init($filterName, ['enabled' => true, 'type' => 'string', 'data_name' => $filterName]);

        $filterForm = $this->filter->getForm();
        $filterForm->submit($filterData);
        self::assertTrue($filterForm->isValid());
        self::assertTrue($filterForm->isSynchronized());
        $data = $filterForm->getData();

        $ds = new OrmFilterDatasourceAdapter($qb);
        $this->filter->apply($ds, $data);

        $result = $ds->getQueryBuilder()->getQuery()->getResult();
        self::assertSame($expectedResult, $result);
    }

    /**
     * @dataProvider filterProvider
     */
    public function testFilterWithoutForm(string $filterName, array $filterData, array $expectedResult)
    {
        $qb = $this->createQueryBuilder('u');
        $qb->select('u.username')
            ->orderBy('u.username')
            ->andWhere($qb->expr()->in('u.username', ['u1', 'u2', 'u3']));

        $this->filter->init($filterName, ['enabled' => true, 'type' => 'string', 'data_name' => $filterName]);

        $data = $this->filter->prepareData($filterData);

        $ds = new OrmFilterDatasourceAdapter($qb);
        $this->filter->apply($ds, $data);

        $result = $ds->getQueryBuilder()->getQuery()->getResult();
        self::assertSame($expectedResult, $result);
    }

    public function filterProvider(): array
    {
        return [
            'Filter "not empty"'        => [
                'filterName'     => 'u.username',
                'filterData'     => [
                    'type'  => FilterUtility::TYPE_NOT_EMPTY,
                    'value' => FilterUtility::TYPE_NOT_EMPTY
                ],
                'expectedResult' => [
                    ['username' => 'u1'],
                    ['username' => 'u2'],
                    ['username' => 'u3']
                ]
            ],
            'Filter "empty"'            => [
                'filterName'     => 'u.username',
                'filterData'     => [
                    'type'  => FilterUtility::TYPE_EMPTY,
                    'value' => FilterUtility::TYPE_EMPTY
                ],
                'expectedResult' => []
            ],
            'Filter "equal"'            => [
                'filterName'     => 'u.username',
                'filterData'     => [
                    'type'  => TextFilterType::TYPE_EQUAL,
                    'value' => 'u1'
                ],
                'expectedResult' => [
                    ['username' => 'u1']
                ]
            ],
            'Filter "contains"'         => [
                'filterName'     => 'u.username',
                'filterData'     => [
                    'type'  => TextFilterType::TYPE_CONTAINS,
                    'value' => 'u'
                ],
                'expectedResult' => [
                    ['username' => 'u1'],
                    ['username' => 'u2'],
                    ['username' => 'u3']
                ]
            ],
            'Filter "does not contain"' => [
                'filterName'     => 'u.username',
                'filterData'     => [
                    'type'  => TextFilterType::TYPE_NOT_CONTAINS,
                    'value' => 'u'
                ],
                'expectedResult' => []
            ],
            'Filter "starts with"'      => [
                'filterName'     => 'u.username',
                'filterData'     => [
                    'type'  => TextFilterType::TYPE_STARTS_WITH,
                    'value' => 'u'
                ],
                'expectedResult' => [
                    ['username' => 'u1'],
                    ['username' => 'u2'],
                    ['username' => 'u3']
                ]
            ],
            'Filter "ends with"'        => [
                'filterName'     => 'u.username',
                'filterData'     => [
                    'type'  => TextFilterType::TYPE_ENDS_WITH,
                    'value' => '3'
                ],
                'expectedResult' => [
                    ['username' => 'u3']
                ]
            ]
        ];
    }

    public function testStringContainsHasRelatedJoin()
    {
        $qb = $this->createQueryBuilder('u');
        $qb->select('u.username, e.email')
            ->join('u.emails', 'e')
            ->orderBy('u.username');

        $this->filter->init('string', ['data_name' => 'e.email']);

        $filterForm = $this->filter->getForm();
        $filterForm->submit(['type' => TextFilterType::TYPE_CONTAINS, 'value' => 'test2']);
        self::assertTrue($filterForm->isValid());
        self::assertTrue($filterForm->isSynchronized());

        $ds = new OrmFilterDatasourceAdapter($qb);
        $this->filter->apply($ds, $filterForm->getData());

        $qb = $ds->getQueryBuilder();

        $actualData = $qb->getQuery()->getResult();
        self::assertCount(1, $actualData);
        self::assertEquals(['username' => 'u2', 'email' => 'test2@example.com'], $actualData[0]);

        $whereParts = $qb->getDQLPart('where')->getParts();
        self::assertCount(1, $whereParts);
        self::assertStringContainsString('EXISTS(SELECT', $whereParts[0]);
        self::assertStringNotContainsString('GROUP BY ', $whereParts[0]);
    }

    public function testStringInHasGroupBy()
    {
        $qb = $this->createQueryBuilder('u');
        $qb->select('u.username, e.email')
            ->join('u.emails', 'e')
            ->orderBy('u.username')
            ->andWhere($qb->expr()->in('u.username', ['u1', 'u2']))
            ->addGroupBy('u.id, e.id');

        $this->filter->init('string', ['data_name' => 'e.email']);

        $filterForm = $this->filter->getForm();
        $filterForm->submit(['type' => TextFilterType::TYPE_IN, 'value' => 'test2@example.com']);
        self::assertTrue($filterForm->isValid());
        self::assertTrue($filterForm->isSynchronized());

        $ds = new OrmFilterDatasourceAdapter($qb);
        $this->filter->apply($ds, $filterForm->getData());

        $qb = $ds->getQueryBuilder();

        $actualData = $qb->getQuery()->getResult();
        self::assertCount(1, $actualData);
        self::assertEquals(['username' => 'u2', 'email' => 'test2@example.com'], $actualData[0]);

        $whereParts = $qb->getDQLPart('where')->getParts();
        self::assertCount(2, $whereParts);
        self::assertStringContainsString('EXISTS(SELECT', $whereParts[1]);
        self::assertStringNotContainsString('GROUP BY ', $whereParts[1]);
    }

    public function testStringInHasGroupByAndHaving()
    {
        $qb = $this->createQueryBuilder('u');
        $qb->select('u.username, e.email')
            ->join('u.emails', 'e')
            ->orderBy('u.username')
            ->andWhere($qb->expr()->in('u.username', ['u1', 'u2']))
            ->addGroupBy('u.id, e.id')
            ->having('MIN(u.id) > 0');

        $this->filter->init('string', ['data_name' => 'e.email']);

        $filterForm = $this->filter->getForm();
        $filterForm->submit(['type' => TextFilterType::TYPE_IN, 'value' => 'test2@example.com']);
        self::assertTrue($filterForm->isValid());
        self::assertTrue($filterForm->isSynchronized());

        $ds = new OrmFilterDatasourceAdapter($qb);
        $this->filter->apply($ds, $filterForm->getData());

        $qb = $ds->getQueryBuilder();

        $actualData = $qb->getQuery()->getResult();
        self::assertCount(1, $actualData);
        self::assertEquals(['username' => 'u2', 'email' => 'test2@example.com'], $actualData[0]);

        $whereParts = $qb->getDQLPart('where')->getParts();
        self::assertCount(2, $whereParts);
        self::assertStringContainsString('EXISTS(SELECT', $whereParts[1]);
        self::assertStringContainsString('GROUP BY ', $whereParts[1]);
    }

    public function testStringNotInHasRelatedJoinWithWhere()
    {
        $qb = $this->createQueryBuilder('u');
        $qb->select('u.username, e.email')
            ->join('u.emails', 'e')
            ->orderBy('u.username')
            ->andWhere($qb->expr()->in('u.username', ['u1', 'u2']));

        $this->filter->init('string', ['data_name' => 'e.email']);

        $filterForm = $this->filter->getForm();
        $filterForm->submit(['type' => TextFilterType::TYPE_NOT_IN, 'value' => 'test1@example.com']);
        self::assertTrue($filterForm->isValid());
        self::assertTrue($filterForm->isSynchronized());

        $ds = new OrmFilterDatasourceAdapter($qb);
        $this->filter->apply($ds, $filterForm->getData());

        $qb = $ds->getQueryBuilder();
        $actualData = $qb->getQuery()->getResult();
        self::assertCount(1, $actualData);

        self::assertEquals(['username' => 'u2', 'email' => 'test2@example.com'], $actualData[0]);
        $whereParts = $qb->getDQLPart('where')->getParts();
        self::assertCount(2, $whereParts);
        self::assertStringContainsString('EXISTS(SELECT', $whereParts[1]);
        self::assertStringNotContainsString('GROUP BY ', $whereParts[1]);
    }
}
