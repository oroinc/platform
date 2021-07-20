<?php

namespace Oro\Bundle\TagBundle\Tests\Functional\Filter;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter;
use Oro\Bundle\FilterBundle\Filter\DictionaryFilter;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\FilterBundle\Form\Type\Filter\DictionaryFilterType;
use Oro\Bundle\FilterBundle\Tests\Functional\Fixtures\LoadUserTags;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * @dbIsolationPerTest
 */
class TagsDictionaryFilterTest extends WebTestCase
{
    /** @var DictionaryFilter */
    private $filter;

    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadUserTags::class]);
        $this->filter = $this->getContainer()->get('oro_tag.filter.tags_choice_tree');
    }

    /**
     * @dataProvider filterProvider
     */
    public function testFilter(callable $filterFormData, array $expectedResult): void
    {
        $qb = $this->createQueryBuilder('u');
        $ds = new OrmFilterDatasourceAdapter($qb);

        $filterForm = $this->filter->getForm();
        $filterForm->submit($filterFormData());

        $this->assertTrue($filterForm->isValid());
        $this->assertTrue($filterForm->isSynchronized());

        $this->filter->init('u.id', ['data_name' => 'u.id', 'entity_class' => User::class]);
        $this->filter->apply($ds, $filterForm->getData());

        $result = $ds->getQueryBuilder()->getQuery()->getResult();

        $this->assertSame($expectedResult, $result);
    }

    /**
     * @return array
     */
    public function filterProvider()
    {
        return [
            'Filter "is any of"' => [
                'filterFormData' => $this->getFilterFormDataCallback(DictionaryFilterType::TYPE_IN, 'tag.Wholesale'),
                'expectedResult' => [
                    ['username' => 'u2'],
                ],
            ],
            'Filter "is not any of"' => [
                'filterFormData' => $this->getFilterFormDataCallback(DictionaryFilterType::TYPE_NOT_IN, 'tag.Friends'),
                'expectedResult' => [
                    ['username' => 'u2'],
                    ['username' => 'u3'],
                ],
            ],
            'Filter "is empty"' => [
                'filterFormData' => $this->getFilterFormDataCallback(FilterUtility::TYPE_EMPTY),
                'expectedResult' => [
                    ['username' => 'u3'],
                ],
            ],
            'Filter "is not empty"' => [
                'filterFormData' => $this->getFilterFormDataCallback(FilterUtility::TYPE_NOT_EMPTY),
                'expectedResult' => [
                    ['username' => 'u1'],
                    ['username' => 'u2'],
                ],
            ],
        ];
    }

    /**
     * @param int $type
     * @param string $reference
     *
     * @return \Closure
     */
    private function getFilterFormDataCallback($type, $reference = null): \Closure
    {
        return function () use ($type, $reference) {
            return [
                'type' => $type,
                'value' => $reference ? [$this->getReference($reference)->getId()] : null,
            ];
        };
    }

    /**
     * @param string $alias
     * @return QueryBuilder
     */
    private function createQueryBuilder($alias): QueryBuilder
    {
        $doctrine = $this->getContainer()->get('doctrine');

        $qb = $doctrine->getManagerForClass(User::class)
            ->getRepository(User::class)
            ->createQueryBuilder($alias);

        return $qb->select($alias . '.username')
            ->orderBy($alias . '.username')
            ->andWhere(
                $qb->expr()->in($alias . '.username', ['u1', 'u2', 'u3'])
            );
    }
}
