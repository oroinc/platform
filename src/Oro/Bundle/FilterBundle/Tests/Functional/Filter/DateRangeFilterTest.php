<?php

namespace Oro\Bundle\FilterBundle\Tests\Functional\Filter;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter;
use Oro\Bundle\FilterBundle\Filter\DateRangeFilter;
use Oro\Bundle\FilterBundle\Form\Type\Filter\DateRangeFilterType;
use Oro\Bundle\FilterBundle\Provider\DateModifierInterface;
use Oro\Bundle\FilterBundle\Tests\Functional\Fixtures\LoadUserWithBUAndOrganization;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\Repository\UserRepository;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * @dbIsolationPerTest
 */
class DateRangeFilterTest extends WebTestCase
{
    private const TODAY = '2020-02-02';
    private const YESTERDAY = '2020-02-01';

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadUserWithBUAndOrganization::class]);
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

    /**
     * @dataProvider filterProvider
     */
    public function testFilterWithForm(callable $filterData, array $expectedResult)
    {
        $this->updateUserCreatedAt();

        $qb = $this->createQueryBuilder('u');
        $qb->select('u.username')
            ->orderBy('u.username');

        $filter = $this->getFilter();
        $filter->init('createdAt', ['data_name' => 'u.createdAt', 'type' => 'date']);

        $filterForm = $filter->getForm();
        $filterForm->submit($filterData());
        self::assertTrue($filterForm->isValid());
        self::assertTrue($filterForm->isSynchronized());

        $ds = new OrmFilterDatasourceAdapter($qb);
        $data = $filterForm->getData();

        $filter->apply($ds, $data);

        $result = $ds->getQueryBuilder()->getQuery()->getResult();
        self::assertSame($expectedResult, $result);
    }

    /**
     * @dataProvider filterProvider
     */
    public function testFilterWithoutForm(callable $filterData, array $expectedResult)
    {
        $this->updateUserCreatedAt();

        $qb = $this->createQueryBuilder('u');
        $qb->select('u.username')
            ->orderBy('u.username');

        $filter = $this->getFilter();
        $filter->init('createdAt', ['data_name' => 'u.createdAt', 'type' => 'date']);

        $data = $filter->prepareData($filterData());

        $ds = new OrmFilterDatasourceAdapter($qb);
        $filter->apply($ds, $data);

        $result = $ds->getQueryBuilder()->getQuery()->getResult();
        self::assertSame($expectedResult, $result);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function filterProvider()
    {
        return [
            'equals' => [
                'filterData' => function () {
                    return [
                        'type' => DateRangeFilterType::TYPE_EQUAL,
                        'value' => [
                            'start' => self::YESTERDAY,
                            'end' => ''
                        ]
                    ];
                },
                'expectedResult' => [
                    ['username' => 'admin']
                ]
            ],
            'not equals' => [
                'filterData' => function () {
                    return [
                        'type' => DateRangeFilterType::TYPE_NOT_EQUAL,
                        'value' => [
                            'start' => '',
                            'end' => self::YESTERDAY
                        ]
                    ];
                },
                'expectedResult' => [
                    ['username' => 'u1'],
                    ['username' => 'u2'],
                    ['username' => 'u3']
                ]
            ],
            'between' => [
                'filterData' => function () {
                    return [
                        'type' => DateRangeFilterType::TYPE_BETWEEN,
                        'value' => [
                            'start' => self::YESTERDAY,
                            'end' => self::YESTERDAY
                        ]
                    ];
                },
                'expectedResult' => [
                    ['username' => 'admin']
                ]
            ],
            'not between' => [
                'filterData' => function () {
                    return [
                        'type' => DateRangeFilterType::TYPE_NOT_BETWEEN,
                        'value' => [
                            'start' => self::YESTERDAY,
                            'end' => self::YESTERDAY
                        ]
                    ];
                },
                'expectedResult' => [
                    ['username' => 'u1'],
                    ['username' => 'u2'],
                    ['username' => 'u3']
                ]
            ],
            'equals today' => [
                'filterData' => function () {
                    return [
                        'type' => DateRangeFilterType::TYPE_EQUAL,
                        'value' => [
                            'start' => sprintf('{{%s}}', DateModifierInterface::VAR_TODAY),
                            'end' => ''
                        ]
                    ];
                },
                'expectedResult' => [
                    ['username' => 'u1'],
                    ['username' => 'u2'],
                    ['username' => 'u3']
                ]
            ],
            'not equals today' => [
                'filterData' => function () {
                    return [
                        'type' => DateRangeFilterType::TYPE_NOT_EQUAL,
                        'value' => [
                            'start' => '',
                            'end' => sprintf('{{%s}}', DateModifierInterface::VAR_TODAY)
                        ]
                    ];
                },
                'expectedResult' => [
                    ['username' => 'admin']
                ]
            ],
            'between today' => [
                'filterData' => function () {
                    return [
                        'type' => DateRangeFilterType::TYPE_BETWEEN,
                        'value' => [
                            'start' => sprintf('{{%s}}', DateModifierInterface::VAR_TODAY),
                            'end' => sprintf('{{%s}}', DateModifierInterface::VAR_TODAY)
                        ]
                    ];
                },
                'expectedResult' => [
                    ['username' => 'u1'],
                    ['username' => 'u2'],
                    ['username' => 'u3']
                ]
            ],
            'not between today' => [
                'filterData' => function () {
                    return [
                        'type' => DateRangeFilterType::TYPE_NOT_BETWEEN,
                        'value' => [
                            'start' => sprintf('{{%s}}', DateModifierInterface::VAR_TODAY),
                            'end' => sprintf('{{%s}}', DateModifierInterface::VAR_TODAY)
                        ]
                    ];
                },
                'expectedResult' => [
                    ['username' => 'admin']
                ]
            ]
        ];
    }

    public function testFilterWithStartOfMonthShouldNotThrowExceptionAndReturnAllData()
    {
        $this->updateUserCreatedAt(true);

        $qb = $this->createQueryBuilder('u');
        $qb->select('u.username')
            ->orderBy('u.username');

        $filterData = [
            'type' => DateRangeFilterType::TYPE_EQUAL,
            'part' => DateModifierInterface::PART_VALUE,
            'value' => [
                'start' => sprintf('{{%s}}', DateModifierInterface::VAR_THIS_MONTH_W_Y),
                'end' => ''
            ]
        ];

        $filter = $this->getFilter();
        $filter->init('createdAt', ['data_name' => 'u.createdAt', 'type' => 'datetime']);

        $filterForm = $filter->getForm();
        $filterForm->submit($filterData);
        self::assertTrue($filterForm->isValid());
        self::assertTrue($filterForm->isSynchronized());

        $data = $filterForm->getData();
        $data['value']['start_original'] = $filterData['value']['start'];

        $ds = new OrmFilterDatasourceAdapter($qb);
        $filter->apply($ds, $data);

        $result = $ds->getQueryBuilder()->getQuery()->getResult();
        self::assertSame(
            [
                ['username' => 'u1'],
                ['username' => 'u2'],
                ['username' => 'u3']
            ],
            $result
        );
    }

    public function testFilterWithInvalidFieldName()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsafe value passed createdAt is NULL; DELETE * FROM oro_user;');

        $this->updateUserCreatedAt();

        $qb = $this->createQueryBuilder('u');
        $qb->select('u.username')
            ->orderBy('u.username');

        $filterData = [
            'type' => DateRangeFilterType::TYPE_EQUAL,
            'part' => DateModifierInterface::PART_VALUE,
            'value' => [
                'start' => sprintf('{{%s}}', DateModifierInterface::VAR_THIS_MONTH_W_Y),
                'end' => ''
            ]
        ];

        $filter = $this->getFilter();
        $filter->init(
            'createdAt',
            [
                'data_name' => 'u.createdAt is NULL; DELETE * FROM oro_user;',
                'type' => 'datetime'
            ]
        );

        $filterForm = $filter->getForm();
        $filterForm->submit($filterData);
        self::assertTrue($filterForm->isValid());
        self::assertTrue($filterForm->isSynchronized());

        $data = $filterForm->getData();
        $data['value']['start_original'] = $filterData['value']['start'];

        $ds = new OrmFilterDatasourceAdapter($qb);
        $filter->apply($ds, $data);
    }

    /**
     * @return DateRangeFilter
     */
    private function getFilter()
    {
        return self::getContainer()->get('oro_filter.date_range_filter');
    }

    private function updateUserCreatedAt($today = false)
    {
        $em = $this->getUserEntityManager();
        $user = $this->getUser();
        if ($today) {
            $date = new \DateTime(self::TODAY, new \DateTimeZone('UTC'));
        } else {
            $date = new \DateTime(self::YESTERDAY, new \DateTimeZone('UTC'));
        }
        $user->setCreatedAt($date);
        $em->persist($user);
        $em->flush($user);
    }

    /**
     * @return UserRepository
     */
    private function getUserRepository()
    {
        return $this->getUserEntityManager()->getRepository(User::class);
    }

    /**
     * @return EntityManager
     */
    private function getUserEntityManager()
    {
        return self::getContainer()->get('doctrine')->getManagerForClass(User::class);
    }

    /**
     * @return User
     */
    private function getUser()
    {
        return $this->getUserRepository()->findOneBy(['username' => 'admin']);
    }
}
