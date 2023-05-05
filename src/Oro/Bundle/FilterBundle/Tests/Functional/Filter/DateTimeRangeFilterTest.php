<?php

namespace Oro\Bundle\FilterBundle\Tests\Functional\Filter;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter;
use Oro\Bundle\FilterBundle\Filter\DateTimeRangeFilter;
use Oro\Bundle\FilterBundle\Form\Type\Filter\DateTimeRangeFilterType;
use Oro\Bundle\FilterBundle\Provider\DateModifierInterface;
use Oro\Bundle\FilterBundle\Tests\Functional\Fixtures\LoadUserWithBUAndOrganization;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\Repository\UserRepository;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * @dbIsolationPerTest
 */
class DateTimeRangeFilterTest extends WebTestCase
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
        $filter->init('createdAt', ['data_name' => 'u.createdAt', 'type' => 'datetime']);

        $filterForm = $filter->getForm();
        $filterForm->submit($filterData());
        self::assertTrue($filterForm->isValid());
        self::assertTrue($filterForm->isSynchronized());

        $data = $filterForm->getData();

        /**
         * Fix timezone for filter datetime field
         */
        $data['value']['start'] = $this->fixTimeZone($data['value']['start']);
        $data['value']['end'] = $this->fixTimeZone($data['value']['end']);

        $ds = new OrmFilterDatasourceAdapter($qb);
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
        $filter->init('createdAt', ['data_name' => 'u.createdAt', 'type' => 'datetime']);

        $data = $filter->prepareData($filterData());

        /**
         * Fix timezone for filter datetime field
         */
        $data['value']['start'] = $this->fixTimeZone($data['value']['start']);
        $data['value']['end'] = $this->fixTimeZone($data['value']['end']);

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
            'equals'            => [
                'filterData'     => function () {
                    return [
                        'type'  => DateTimeRangeFilterType::TYPE_EQUAL,
                        'value' => [
                            'start' => $this->getUser()->getCreatedAt()->format('Y-m-d H:i'),
                            'end'   => ''
                        ]
                    ];
                },
                'expectedResult' => [
                    ['username' => 'admin']
                ]
            ],
            'not equals'        => [
                'filterData'     => function () {
                    return [
                        'type'  => DateTimeRangeFilterType::TYPE_NOT_EQUAL,
                        'value' => [
                            'start' => '',
                            'end'   => $this->getUser()->getCreatedAt()->format('Y-m-d H:i')
                        ]
                    ];
                },
                'expectedResult' => [
                    ['username' => 'u1'],
                    ['username' => 'u2'],
                    ['username' => 'u3']
                ]
            ],
            'between'           => [
                'filterData'     => function () {
                    return [
                        'type'  => DateTimeRangeFilterType::TYPE_BETWEEN,
                        'value' => [
                            'start' => $this->getUser()->getCreatedAt()->format('Y-m-d H:i'),
                            'end'   => $this->getUser()->getCreatedAt()->format('Y-m-d H:i')
                        ]
                    ];
                },
                'expectedResult' => [
                    ['username' => 'admin']
                ]
            ],
            'not between'       => [
                'filterData'     => function () {
                    return [
                        'type'  => DateTimeRangeFilterType::TYPE_NOT_BETWEEN,
                        'value' => [
                            'start' => $this->getUser()->getCreatedAt()->format('Y-m-d H:i'),
                            'end'   => $this->getUser()->getCreatedAt()->format('Y-m-d H:i')
                        ]
                    ];
                },
                'expectedResult' => [
                    ['username' => 'u1'],
                    ['username' => 'u2'],
                    ['username' => 'u3']
                ]
            ],
            'equals today'      => [
                'filterData'     => function () {
                    return [
                        'type'  => DateTimeRangeFilterType::TYPE_EQUAL,
                        'value' => [
                            'start' => sprintf('{{%s}}', DateModifierInterface::VAR_TODAY),
                            'end'   => ''
                        ]
                    ];
                },
                'expectedResult' => [
                    ['username' => 'u1'],
                    ['username' => 'u2'],
                    ['username' => 'u3']
                ]
            ],
            'not equals today'  => [
                'filterData'     => function () {
                    return [
                        'type'  => DateTimeRangeFilterType::TYPE_NOT_EQUAL,
                        'value' => [
                            'start' => '',
                            'end'   => sprintf('{{%s}}', DateModifierInterface::VAR_TODAY)
                        ]
                    ];
                },
                'expectedResult' => [
                    ['username' => 'admin']
                ]
            ],
            'between today'     => [
                'filterData'     => function () {
                    return [
                        'type'  => DateTimeRangeFilterType::TYPE_BETWEEN,
                        'value' => [
                            'start' => sprintf('{{%s}}', DateModifierInterface::VAR_TODAY),
                            'end'   => sprintf('{{%s}}', DateModifierInterface::VAR_TODAY)
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
                'filterData'     => function () {
                    return [
                        'type'  => DateTimeRangeFilterType::TYPE_NOT_BETWEEN,
                        'value' => [
                            'start' => sprintf('{{%s}}', DateModifierInterface::VAR_TODAY),
                            'end'   => sprintf('{{%s}}', DateModifierInterface::VAR_TODAY)
                        ]
                    ];
                },
                'expectedResult' => [
                    ['username' => 'admin']
                ]
            ]
        ];
    }

    private function getFilter(): DateTimeRangeFilter
    {
        return self::getContainer()->get('oro_filter.datetime_range_filter');
    }

    private function fixTimeZone(mixed $value): mixed
    {
        if ($value instanceof \DateTime) {
            $value = new \DateTime($value->format('Y-m-d H:i'), new \DateTimeZone('UTC'));
        }

        return $value;
    }

    private function updateUserCreatedAt()
    {
        $em = $this->getUserEntityManager();
        $user = $this->getUser();
        $dateFilter = clone $user->getCreatedAt();
        $user->setCreatedAt($dateFilter->modify('-1 day'));
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
