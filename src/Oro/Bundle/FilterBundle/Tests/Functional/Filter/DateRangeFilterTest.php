<?php

namespace Oro\Bundle\FilterBundle\Tests\Functional\Filter;

use Doctrine\ORM\EntityManagerInterface;
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
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadUserWithBUAndOrganization::class]);
    }

    private function createQueryBuilder(string $alias): QueryBuilder
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
    public function filterProvider(): array
    {
        return [
            'equals' => [
                'filterData' => function () {
                    return [
                        'type' => DateRangeFilterType::TYPE_EQUAL,
                        'value' => [
                            'start' => $this->getUser()->getCreatedAt()->format('Y-m-d'),
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
                            'end' => $this->getUser()->getCreatedAt()->format('Y-m-d')
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
                            'start' => $this->getUser()->getCreatedAt()->format('Y-m-d'),
                            'end' => $this->getUser()->getCreatedAt()->format('Y-m-d')
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
                            'start' => $this->getUser()->getCreatedAt()->format('Y-m-d'),
                            'end' => $this->getUser()->getCreatedAt()->format('Y-m-d')
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
                ['username' => 'admin'],
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

    private function getFilter(): DateRangeFilter
    {
        return self::getContainer()->get('oro_filter.date_range_filter');
    }

    private function updateUserCreatedAt(bool $today = false): void
    {
        $em = $this->getUserEntityManager();
        $user = $this->getUser();
        if ($today) {
            $date = new \DateTime('now', new \DateTimeZone('UTC'));
        } else {
            $dateFilter = clone $user->getCreatedAt();
            $date = $dateFilter->modify('-1 day');
        }
        $user->setCreatedAt($date);
        $em->persist($user);
        $em->flush($user);
    }

    private function getUserRepository(): UserRepository
    {
        return $this->getUserEntityManager()->getRepository(User::class);
    }

    private function getUserEntityManager(): EntityManagerInterface
    {
        return self::getContainer()->get('doctrine')->getManagerForClass(User::class);
    }

    private function getUser(): User
    {
        return $this->getUserRepository()->findOneBy(['username' => 'admin']);
    }
}
