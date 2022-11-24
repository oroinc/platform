<?php

namespace Oro\Bundle\TagBundle\Tests\Functional\Filter;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\FilterBundle\Form\Type\Filter\DictionaryFilterType;
use Oro\Bundle\FilterBundle\Tests\Functional\Fixtures\LoadUserTags;
use Oro\Bundle\TagBundle\Filter\TagsDictionaryFilter;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * @dbIsolationPerTest
 */
class TagsDictionaryFilterTest extends WebTestCase
{
    private TagsDictionaryFilter $filter;

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
        /** @var EntityRepository $repo */
        $repo = $this->getContainer()->get('doctrine')->getRepository(User::class);
        $qb = $repo->createQueryBuilder('u')
            ->select('u.username')
            ->where('u.username IN (:usernames)')
            ->setParameter('usernames', ['u1', 'u2', 'u3'])
            ->orderBy('u.username');
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

    public function filterProvider(): array
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

    private function getFilterFormDataCallback(int|string $type, ?string $reference = null): callable
    {
        return function () use ($type, $reference) {
            return [
                'type' => $type,
                'value' => $reference ? [$this->getReference($reference)->getId()] : null,
            ];
        };
    }
}
