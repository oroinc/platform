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
    /** @var StringFilter */
    protected $filter;

    public function setUp()
    {
        $this->initClient();
        $this->loadFixtures([LoadUserData::class]);
        $this->filter = $this->getContainer()->get('oro_filter.string_filter');
    }

    /**
     * @dataProvider filterProvider
     *
     * @param string $filterName
     * @param array $filterFormData
     * @param array $expectedResult
     */
    public function testFilter($filterName, array $filterFormData, array $expectedResult)
    {
        $qb = $this->createQueryBuilder('u');
        $qb->select('u.username')
            ->orderBy('u.username')
            ->andWhere($qb->expr()->in('u.username', ['u1', 'u2', 'u3']));

        $ds = new OrmFilterDatasourceAdapter($qb);

        $filterForm = $this->filter->getForm();
        $filterForm->submit($filterFormData);

        $this->assertTrue($filterForm->isValid());

        $this->filter->init($filterName, ['enabled' => true, 'type' => 'string', 'data_name' => $filterName]);
        $this->filter->apply($ds, $filterForm->getData());

        $this->assertSame($expectedResult, $ds->getQueryBuilder()->getQuery()->getResult());
    }

    /**
     * @return array
     */
    public function filterProvider()
    {
        return [
            'Filter "not empty"' => [
                'filterName' => 'u.username',
                'filterFormData' => [
                    'type' => FilterUtility::TYPE_NOT_EMPTY,
                    'value' => FilterUtility::TYPE_NOT_EMPTY,
                ],
                'expectedResult' => [
                    ['username' => 'u1'],
                    ['username' => 'u2'],
                    ['username' => 'u3']
                ],
            ],
            'Filter "empty"' => [
                'filterName' => 'u.username',
                'filterFormData' => [
                    'type' => FilterUtility::TYPE_EMPTY,
                    'value' => FilterUtility::TYPE_EMPTY,
                ],
                'expectedResult' => [ ],
            ],
            'Filter "equal"' => [
                'filterName' => 'u.username',
                'filterFormData' => [
                    'type' => TextFilterType::TYPE_EQUAL,
                    'value' => 'u1',
                ],
                'expectedResult' => [
                    ['username' => 'u1']
                ],
            ],
            'Filter "contains"' => [
                'filterName' => 'u.username',
                'filterFormData' => [
                    'type' => TextFilterType::TYPE_CONTAINS,
                    'value' => 'u',
                ],
                'expectedResult' => [
                    ['username' => 'u1'],
                    ['username' => 'u2'],
                    ['username' => 'u3']
                ],
            ],
            'Filter "does not contain"' => [
                'filterName' => 'u.username',
                'filterFormData' => [
                    'type' => TextFilterType::TYPE_NOT_CONTAINS,
                    'value' => 'u',
                ],
                'expectedResult' => [],
            ],
            'Filter "starts with"' => [
                'filterName' => 'u.username',
                'filterFormData' => [
                    'type' => TextFilterType::TYPE_STARTS_WITH,
                    'value' => 'u',
                ],
                'expectedResult' => [
                    ['username' => 'u1'],
                    ['username' => 'u2'],
                    ['username' => 'u3']
                ],
            ],
            'Filter "ends with"' => [
                'filterName' => 'u.username',
                'filterFormData' => [
                    'type' => TextFilterType::TYPE_ENDS_WITH,
                    'value' => '3',
                ],
                'expectedResult' => [
                    ['username' => 'u3']
                ],
            ]
        ];
    }

    public function testStringContainsHasRelatedJoin()
    {
        $qb = $this->createQueryBuilder('u');
        $qb->select('u.username, e.email')
            ->join('u.emails', 'e')
            ->orderBy('u.username');

        $ds = new OrmFilterDatasourceAdapter($qb);

        $filterForm = $this->filter->getForm();
        $filterForm->submit(['type' => TextFilterType::TYPE_CONTAINS, 'value' => 'test2']);

        $this->assertTrue($filterForm->isValid());

        $this->filter->init('string', ['data_name' => 'e.email']);
        $this->filter->apply($ds, $filterForm->getData());

        $qb = $ds->getQueryBuilder();

        $actualData = $qb->getQuery()->getResult();
        $this->assertCount(1, $actualData);
        $this->assertEquals(['username' => 'u2', 'email' => 'test2@example.com'], $actualData[0]);

        $whereParts = $qb->getDQLPart('where')->getParts();
        $this->assertCount(1, $whereParts);
        $this->assertContains('EXISTS(SELECT', $whereParts[0]);
        $this->assertNotContains('GROUP BY ', $whereParts[0]);
    }

    public function testStringInHasGroupBy()
    {
        $qb = $this->createQueryBuilder('u');
        $qb->select('u.username, e.email')
            ->join('u.emails', 'e')
            ->orderBy('u.username')
            ->andWhere($qb->expr()->in('u.username', ['u1', 'u2']))
            ->addGroupBy('u.id, e.id');

        $ds = new OrmFilterDatasourceAdapter($qb);

        $filterForm = $this->filter->getForm();
        $filterForm->submit(['type' => TextFilterType::TYPE_IN, 'value' => 'test2@example.com']);

        $this->assertTrue($filterForm->isValid());

        $this->filter->init('string', ['data_name' => 'e.email']);
        $this->filter->apply($ds, $filterForm->getData());

        $qb = $ds->getQueryBuilder();

        $actualData = $qb->getQuery()->getResult();
        $this->assertCount(1, $actualData);
        $this->assertEquals(['username' => 'u2', 'email' => 'test2@example.com'], $actualData[0]);

        $whereParts = $qb->getDQLPart('where')->getParts();
        $this->assertCount(2, $whereParts);
        $this->assertContains('EXISTS(SELECT', $whereParts[1]);
        $this->assertContains('GROUP BY ', $whereParts[1]);
    }

    public function testStringNotInHasRelatedJoinWithWhere()
    {
        $qb = $this->createQueryBuilder('u');
        $qb->select('u.username, e.email')
            ->join('u.emails', 'e')
            ->orderBy('u.username')
            ->andWhere($qb->expr()->in('u.username', ['u1', 'u2']));

        $ds = new OrmFilterDatasourceAdapter($qb);

        $filterForm = $this->filter->getForm();
        $filterForm->submit(['type' => TextFilterType::TYPE_NOT_IN, 'value' => 'test1@example.com']);

        $this->assertTrue($filterForm->isValid());

        $this->filter->init('string', ['data_name' => 'e.email']);
        $this->filter->apply($ds, $filterForm->getData());

        $qb = $ds->getQueryBuilder();
        $actualData = $qb->getQuery()->getResult();
        $this->assertCount(1, $actualData);

        $this->assertEquals(['username' => 'u2', 'email' => 'test2@example.com'], $actualData[0]);
        $whereParts = $qb->getDQLPart('where')->getParts();
        $this->assertCount(2, $whereParts);
        $this->assertContains('EXISTS(SELECT', $whereParts[1]);
        $this->assertNotContains('GROUP BY ', $whereParts[1]);
    }

    /**
     * @param string $alias
     * @return QueryBuilder
     */
    protected function createQueryBuilder($alias)
    {
        $doctrine = $this->getContainer()->get('doctrine');
        $objectManager = $doctrine->getManagerForClass(User::class);
        $repository = $objectManager->getRepository(User::class);

        return $repository->createQueryBuilder($alias);
    }
}
