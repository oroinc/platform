<?php

namespace Oro\Bundle\FilterBundle\Tests\Functional\Filter;

use Doctrine\ORM\EntityManager;
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
    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->initClient();
        $this->loadFixtures([LoadUserWithBUAndOrganization::class]);
    }

    /**
     * @dataProvider filterProvider
     *
     * @param callable $filterFormData
     * @param array    $expectedResult
     */
    public function testFilter(callable $filterFormData, array $expectedResult)
    {
        $this->updateUserCreatedAt();
        $qb = $this->createQueryBuilder('u');
        $qb
            ->select('u.username')
            ->orderBy('u.username');

        $ds = new OrmFilterDatasourceAdapter($qb);

        $filter = $this->getFilter();
        $filterForm = $filter->getForm();
        $filterForm->submit($filterFormData());

        self::assertTrue($filterForm->isValid());

        $filter->init(
            'createdAt',
            [
                'data_name' => 'u.createdAt',
                'type'      => 'datetime'
            ]
        );
        $formData = $filterForm->getData();

        /**
         * Fix timezone for filter datetime field
         */
        $formData['value']['start'] = $this->fixTimeZone($formData['value']['start']);
        $formData['value']['end'] = $this->fixTimeZone($formData['value']['end']);

        $filter->apply($ds, $formData);

        $result = $ds->getQueryBuilder()->getQuery()->getResult();
        self::assertSame($expectedResult, $result);
    }

    /**
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function filterProvider()
    {
        return [
            'equals'            => [
                'filterFormData' => function () {
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
                'filterFormData' => function () {
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
                'filterFormData' => function () {
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
                'filterFormData' => function () {
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
                'filterFormData' => function () {
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
                'filterFormData' => function () {
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
                'filterFormData' => function () {
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
                'filterFormData' => function () {
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

    /**
     * @return DateTimeRangeFilter
     */
    private function getFilter()
    {
        return self::getContainer()->get('oro_filter.datetime_range_filter');
    }

    /**
     * @param mixed $value
     *
     * @return mixed
     */
    private function fixTimeZone($value)
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
