<?php

namespace Oro\Bundle\FilterBundle\Tests\Functional\Filter;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter;
use Oro\Bundle\FilterBundle\Filter\DateTimeRangeFilter;
use Oro\Bundle\FilterBundle\Form\Type\Filter\DateTimeRangeFilterType;
use Oro\Bundle\FilterBundle\Tests\Functional\Fixtures\LoadUserWithBUAndOrganization;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\Repository\UserRepository;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * @dbIsolationPerTest
 */
class DateTimeRangeFilterTest extends WebTestCase
{
    /** @var DateTimeRangeFilter */
    protected $filter;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->initClient();
        $this->loadFixtures([LoadUserWithBUAndOrganization::class]);
        $this->filter = $this->getContainer()->get('oro_filter.datetime_range_filter');
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->filter);
    }

    /**
     * @dataProvider filterProvider
     *
     * @param callable $filterFormData
     * @param array $expectedResult
     */
    public function testFilter(callable $filterFormData, array $expectedResult)
    {
        $qb = $this->createQueryBuilder('u');
        $qb
            ->select('u.username')
            ->orderBy('u.username');

        $ds = new OrmFilterDatasourceAdapter($qb);

        $filterForm = $this->filter->getForm();
        $filterForm->submit($filterFormData());

        $this->assertTrue($filterForm->isValid());

        $this->filter->init(
            'updatedAt',
            [
                'data_name' => 'u.updatedAt',
                'type'      => 'datetime'
            ]
        );
        $formData = $filterForm->getData();

        /**
         * Fix timezone for filter datetime field
         */
        $index = $formData['type'] === DateTimeRangeFilterType::TYPE_EQUAL ? 'start' : 'end';
        /** @var \DateTime $dateTimeValue */
        $dateTimeValue = $formData['value'][$index];
        $timeString = $dateTimeValue->format('Y-m-d H:i');
        $time = new \DateTime($timeString, new \DateTimeZone('UTC'));
        $formData['value'][$index] = $time;

        $this->filter->apply($ds, $formData);

        $result = $ds->getQueryBuilder()->getQuery()->getResult();
        $this->assertSame($expectedResult, $result);
    }

    /**
     * @return array
     */
    public function filterProvider()
    {
        return [
            'Filter "equal' => [
                'filterFormData' => $this->getFilterFormEqualCallback(),
                'expectedResult' => [
                    ['username' => 'admin'],
                ],
            ],
            'Filter "not equal"' => [
                'filterFormData' => $this->getFilterFormNotEqualCallback(),
                'expectedResult' => [
                    ['username' => 'u1'],
                    ['username' => 'u2'],
                    ['username' => 'u3'],
                ],
            ]
        ];
    }

    /**
     * @return \Closure
     */
    private function getFilterFormEqualCallback()
    {
        return function () {
            $updatedAt = $this->getUser()->getUpdatedAt();
            return [
                'type' => DateTimeRangeFilterType::TYPE_EQUAL,
                'value' => [
                    'start' => $updatedAt->format('Y-m-d H:i'),
                    'end'   => ""
                ],
            ];
        };
    }

    /**
     * @return \Closure
     */
    private function getFilterFormNotEqualCallback()
    {
        return function () {
            $updatedAt = $this->getUser()->getUpdatedAt();
            return [
                'type' => DateTimeRangeFilterType::TYPE_NOT_EQUAL,
                'value' => [
                    'start' => "",
                    'end'   => $updatedAt->format('Y-m-d H:i')
                ],
            ];
        };
    }

    /**
     * @return UserRepository
     */
    private function getUserRepository()
    {
        $doctrine = $this->getContainer()->get('doctrine');
        $objectManager = $doctrine->getManagerForClass(User::class);
        $repository = $objectManager->getRepository(User::class);

        return $repository;
    }

    /**
     * @return User
     */
    private function getUser()
    {
        /** @var User $user */
        $user = $this->getUserRepository()->findOneBy(['username' => 'admin']);

        return $user;
    }

    /**
     * @param string $alias
     *
     * @return QueryBuilder
     */
    private function createQueryBuilder($alias)
    {
        return $this->getUserRepository()->createQueryBuilder($alias);
    }
}
