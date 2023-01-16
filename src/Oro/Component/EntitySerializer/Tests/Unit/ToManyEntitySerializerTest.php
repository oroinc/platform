<?php

namespace Oro\Component\EntitySerializer\Tests\Unit;

use Oro\Component\EntitySerializer\Tests\Unit\Fixtures\Entity\Product;
use Oro\Component\EntitySerializer\Tests\Unit\Fixtures\Entity\Role;
use Oro\Component\EntitySerializer\Tests\Unit\Fixtures\Entity\User;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ToManyEntitySerializerTest extends EntitySerializerTestCase
{
    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testManyToManyUnidirectional(): void
    {
        $qb = $this->em->getRepository(User::class)->createQueryBuilder('e')
            ->where('e.id IN (:ids)')
            ->setParameter('ids', [123, 456]);

        $this->addQueryExpectation(
            'SELECT u0_.id AS id_0, u0_.name AS name_1'
            . ' FROM user_table u0_'
            . ' WHERE u0_.id IN (?, ?)',
            [
                [
                    'id_0'   => 123,
                    'name_1' => 'user_name1'
                ],
                [
                    'id_0'   => 456,
                    'name_1' => 'user_name2'
                ]
            ],
            [1 => 123, 2 => 456],
            [1 => \PDO::PARAM_INT, 2 => \PDO::PARAM_INT]
        );
        $this->addQueryExpectation(
            'SELECT u0_.id AS id_0,'
            . ' g1_.id AS id_1, g1_.name AS name_2, g1_.label AS label_3, g1_.public AS public_4'
            . ' FROM user_table u0_'
            . ' INNER JOIN rel_user_to_group_table r2_ ON u0_.id = r2_.user_id'
            . ' INNER JOIN group_table g1_ ON g1_.id = r2_.user_group_id'
            . ' WHERE u0_.id IN (?, ?)',
            [
                [
                    'id_0'     => 123,
                    'id_1'     => 10,
                    'name_2'   => 'group_name1',
                    'label_3'  => 'group_label1',
                    'public_4' => 0
                ],
                [
                    'id_0'     => 123,
                    'id_1'     => 20,
                    'name_2'   => 'group_name2',
                    'label_3'  => 'group_label2',
                    'public_4' => true
                ]
            ],
            [1 => 123, 2 => 456],
            [1 => \PDO::PARAM_INT, 2 => \PDO::PARAM_INT]
        );
        $this->applyQueryExpectations($this->getDriverConnectionMock($this->em));

        $result = $this->serializer->serialize(
            $qb,
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'id'     => null,
                    'name'   => null,
                    'groups' => [
                        'fields' => [
                            'isException' => [
                                'exclude' => true
                            ]
                        ]
                    ]
                ]
            ]
        );

        $this->assertArrayEquals(
            [
                [
                    'id'     => 123,
                    'name'   => 'user_name1',
                    'groups' => [
                        [
                            'id'     => 10,
                            'name'   => 'group_name1',
                            'label'  => 'group_label1',
                            'public' => false
                        ],
                        [
                            'id'     => 20,
                            'name'   => 'group_name2',
                            'label'  => 'group_label2',
                            'public' => true
                        ]
                    ]
                ],
                [
                    'id'     => 456,
                    'name'   => 'user_name2',
                    'groups' => []
                ]
            ],
            $result
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testManyToManyUnidirectionalWithSubQueryLimit(): void
    {
        $qb = $this->em->getRepository(User::class)->createQueryBuilder('e')
            ->where('e.id IN (:ids)')
            ->setParameter('ids', [123, 456]);

        $this->addQueryExpectation(
            'SELECT u0_.id AS id_0, u0_.name AS name_1'
            . ' FROM user_table u0_'
            . ' WHERE u0_.id IN (?, ?)',
            [
                [
                    'id_0'   => 123,
                    'name_1' => 'user_name1'
                ],
                [
                    'id_0'   => 456,
                    'name_1' => 'user_name2'
                ]
            ],
            [1 => 123, 2 => 456],
            [1 => \PDO::PARAM_INT, 2 => \PDO::PARAM_INT]
        );
        $this->addQueryExpectation(
            'SELECT entity.id_0 AS entityId, entity.id_1 AS relatedEntityId'
            . ' FROM (('
            . 'SELECT u0_.id AS id_0, g1_.id AS id_1'
            . ' FROM user_table u0_'
            . ' INNER JOIN rel_user_to_group_table r2_ ON u0_.id = r2_.user_id'
            . ' INNER JOIN group_table g1_ ON g1_.id = r2_.user_group_id'
            . ' WHERE u0_.id = 123 LIMIT 10'
            . ') UNION ALL ('
            . 'SELECT u0_.id AS id_0, g1_.id AS id_1'
            . ' FROM user_table u0_'
            . ' INNER JOIN rel_user_to_group_table r2_ ON u0_.id = r2_.user_id'
            . ' INNER JOIN group_table g1_ ON g1_.id = r2_.user_group_id'
            . ' WHERE u0_.id = 456 LIMIT 10'
            . ')) entity',
            [
                [
                    'entityId'        => '123',
                    'relatedEntityId' => '10'
                ],
                [
                    'entityId'        => '123',
                    'relatedEntityId' => '20'
                ]
            ]
        );
        $this->addQueryExpectation(
            'SELECT g0_.id AS id_0, g0_.name AS name_1, g0_.label AS label_2, g0_.public AS public_3'
            . ' FROM group_table g0_'
            . ' WHERE g0_.id IN (?, ?)',
            [
                [
                    'id_0'     => 10,
                    'name_1'   => 'group_name1',
                    'label_2'  => 'group_label1',
                    'public_3' => 0
                ],
                [
                    'id_0'     => 20,
                    'name_1'   => 'group_name2',
                    'label_2'  => 'group_label2',
                    'public_3' => true
                ]
            ],
            [1 => 10, 2 => 20],
            [1 => \PDO::PARAM_INT, 2 => \PDO::PARAM_INT]
        );
        $this->applyQueryExpectations($this->getDriverConnectionMock($this->em));

        $result = $this->serializer->serialize(
            $qb,
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'id'     => null,
                    'name'   => null,
                    'groups' => [
                        'max_results' => 10,
                        'fields'      => [
                            'isException' => [
                                'exclude' => true
                            ]
                        ]
                    ]
                ]
            ]
        );

        $this->assertArrayEquals(
            [
                [
                    'id'     => 123,
                    'name'   => 'user_name1',
                    'groups' => [
                        [
                            'id'     => 10,
                            'name'   => 'group_name1',
                            'label'  => 'group_label1',
                            'public' => false
                        ],
                        [
                            'id'     => 20,
                            'name'   => 'group_name2',
                            'label'  => 'group_label2',
                            'public' => true
                        ]
                    ]
                ],
                [
                    'id'     => 456,
                    'name'   => 'user_name2',
                    'groups' => []
                ]
            ],
            $result
        );
    }

    public function testSubQueryLimitAndStringEntityId(): void
    {
        $qb = $this->em->getRepository(Role::class)->createQueryBuilder('e')
            ->where('e.code IN (:ids)')
            ->setParameter('ids', ['id1', 'id2']);

        $this->addQueryExpectation(
            'SELECT r0_.code AS code_0, c1_.name AS name_1'
            . ' FROM role_table r0_'
            . ' LEFT JOIN category_table c1_ ON r0_.category_name = c1_.name'
            . ' WHERE r0_.code IN (?, ?)',
            [
                [
                    'code_0' => 'id1',
                    'name_1' => 'category_1'
                ],
                [
                    'code_0' => 'id2',
                    'name_1' => null
                ]
            ],
            [1 => 'id1', 2 => 'id2'],
            [1 => \PDO::PARAM_STR, 2 => \PDO::PARAM_STR]
        );
        $this->addQueryExpectation(
            'SELECT entity.code_0 AS entityId, entity.id_1 AS relatedEntityId'
            . ' FROM (('
            . 'SELECT r0_.code AS code_0, g1_.id AS id_1'
            . ' FROM role_table r0_'
            . ' INNER JOIN rel_role_to_group_table r2_ ON r0_.code = r2_.role_code'
            . ' INNER JOIN group_table g1_ ON g1_.id = r2_.role_group_id'
            . ' WHERE r0_.code = \'id1\' LIMIT 10'
            . ') UNION ALL ('
            . 'SELECT r0_.code AS code_0, g1_.id AS id_1'
            . ' FROM role_table r0_'
            . ' INNER JOIN rel_role_to_group_table r2_ ON r0_.code = r2_.role_code'
            . ' INNER JOIN group_table g1_ ON g1_.id = r2_.role_group_id'
            . ' WHERE r0_.code = \'id2\' LIMIT 10'
            . ')) entity',
            [
                [
                    'entityId'        => 'id1',
                    'relatedEntityId' => 10
                ],
                [
                    'entityId'        => 'id1',
                    'relatedEntityId' => 20
                ]
            ]
        );
        $this->applyQueryExpectations($this->getDriverConnectionMock($this->em));

        $result = $this->serializer->serialize(
            $qb,
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'code'     => null,
                    'category' => [
                        'fields' => 'name'
                    ],
                    'groups'   => [
                        'max_results' => 10,
                        'fields'      => 'id'
                    ]
                ]
            ]
        );

        $this->assertArrayEquals(
            [
                [
                    'code'     => 'id1',
                    'category' => 'category_1',
                    'groups'   => [10, 20]
                ],
                [
                    'code'     => 'id2',
                    'category' => null,
                    'groups'   => []
                ]
            ],
            $result
        );
    }

    /**
     * @dataProvider subQueryLimitAndEntityCountMoreThanUnionQueryLimitDataProvider
     */
    public function testSubQueryLimitAndEntityCountMoreThanUnionQueryLimit(int $entityCount): void
    {
        $unionQueryLimit = 100;
        $entityRows = [];
        $unionQueries = [];
        $unionQueriesRows = [];
        $unionQueriesGroupIndex = 0;
        $unionQueriesGroups = [];
        $relatedEntityIds = [];
        $relatedEntityIdsTypes = [];
        $relatedEntityRows = [];
        $expectedResult = [];
        for ($i = 1; $i <= $entityCount; $i++) {
            $relatedEntityId = 10000 + $i;
            $entityRows[] = ['id_0' => $i, 'name_1' => 'user_name' . $i];
            $unionQueries[] = sprintf('SELECT u0_.id AS id_0, g1_.id AS id_1'
                . ' FROM user_table u0_'
                . ' INNER JOIN rel_user_to_group_table r2_ ON u0_.id = r2_.user_id'
                . ' INNER JOIN group_table g1_ ON g1_.id = r2_.user_group_id'
                . ' WHERE u0_.id = %d LIMIT 10', $i);
            $unionQueriesRows[] = ['entityId' => $i, 'relatedEntityId' => $relatedEntityId];
            if (($i - ($unionQueryLimit * $unionQueriesGroupIndex)) >= $unionQueryLimit) {
                $unionQueriesGroups[$unionQueriesGroupIndex] = [$unionQueries, $unionQueriesRows];
                $unionQueries = [];
                $unionQueriesRows = [];
                $unionQueriesGroupIndex++;
            }
            $relatedEntityIds[$i] = $relatedEntityId;
            $relatedEntityIdsTypes[$i] = \PDO::PARAM_INT;
            $relatedEntityRows[] = ['id_0' => $relatedEntityId, 'name_1' => 'group_name' . $i];
            $expectedResult[] = [
                'id'     => $i,
                'name'   => 'user_name' . $i,
                'groups' => [
                    ['id' => $relatedEntityId, 'name' => 'group_name' . $i]
                ]
            ];
        }
        if ($unionQueries) {
            $unionQueriesGroups[$unionQueriesGroupIndex] = [$unionQueries, $unionQueriesRows];
        }

        $this->addQueryExpectation(
            'SELECT u0_.id AS id_0, u0_.name AS name_1 FROM user_table u0_',
            $entityRows
        );
        foreach ($unionQueriesGroups as $unionQueries) {
            $this->addQueryExpectation(
                'SELECT entity.id_0 AS entityId, entity.id_1 AS relatedEntityId'
                . ' FROM ((' . implode(') UNION ALL (', $unionQueries[0]) . ')) entity',
                $unionQueries[1]
            );
        }
        $this->addQueryExpectation(
            'SELECT g0_.id AS id_0, g0_.name AS name_1'
            . ' FROM group_table g0_'
            . ' WHERE g0_.id IN (' . implode(', ', array_fill(0, count($relatedEntityIds), '?')) . ')',
            $relatedEntityRows,
            $relatedEntityIds,
            $relatedEntityIdsTypes
        );
        $this->applyQueryExpectations($this->getDriverConnectionMock($this->em));

        $result = $this->serializer->serialize(
            $this->em->getRepository(User::class)->createQueryBuilder('e'),
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'id'     => null,
                    'name'   => null,
                    'groups' => [
                        'max_results'      => 10,
                        'exclusion_policy' => 'all',
                        'fields'           => [
                            'id'   => null,
                            'name' => null
                        ]
                    ]
                ]
            ]
        );

        $this->assertArrayEquals($expectedResult, $result);
    }

    public function subQueryLimitAndEntityCountMoreThanUnionQueryLimitDataProvider(): array
    {
        return [
            [99],
            [100],
            [101],
            [199],
            [200],
            [201]
        ];
    }

    public function testManyToManyUnidirectionalIdOnly(): void
    {
        $qb = $this->em->getRepository(User::class)->createQueryBuilder('e')
            ->where('e.id = :id')
            ->setParameter('id', 1);

        $this->addQueryExpectation(
            'SELECT u0_.id AS id_0, u0_.name AS name_1'
            . ' FROM user_table u0_'
            . ' WHERE u0_.id = ?',
            [
                [
                    'id_0'   => 1,
                    'name_1' => 'user_name'
                ]
            ],
            [1 => 1],
            [1 => \PDO::PARAM_INT]
        );
        $this->addQueryExpectation(
            'SELECT u0_.id AS id_0, g1_.id AS id_1'
            . ' FROM user_table u0_'
            . ' INNER JOIN rel_user_to_group_table r2_ ON u0_.id = r2_.user_id'
            . ' INNER JOIN group_table g1_ ON g1_.id = r2_.user_group_id'
            . ' WHERE u0_.id = ?',
            [
                [
                    'id_0' => 1,
                    'id_1' => 10
                ],
                [
                    'id_0' => 1,
                    'id_1' => 20
                ]
            ],
            [1 => 1],
            [1 => \PDO::PARAM_INT]
        );
        $this->applyQueryExpectations($this->getDriverConnectionMock($this->em));

        $result = $this->serializer->serialize(
            $qb,
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'id'     => null,
                    'name'   => null,
                    'groups' => [
                        'fields' => 'id'
                    ]
                ]
            ]
        );

        $this->assertArrayEquals(
            [
                [
                    'id'     => 1,
                    'name'   => 'user_name',
                    'groups' => [10, 20]
                ]
            ],
            $result
        );
    }

    public function testManyToManyBidirectionalIdOnlyAndOrderBy(): void
    {
        $qb = $this->em->getRepository(User::class)->createQueryBuilder('e')
            ->where('e.id = :id')
            ->setParameter('id', 1);

        $this->addQueryExpectation(
            'SELECT u0_.id AS id_0, u0_.name AS name_1'
            . ' FROM user_table u0_'
            . ' WHERE u0_.id = ?',
            [
                [
                    'id_0'   => 1,
                    'name_1' => 'user_name'
                ]
            ],
            [1 => 1],
            [1 => \PDO::PARAM_INT]
        );
        $this->addQueryExpectation(
            'SELECT u0_.id AS id_0,'
            . ' p1_.id AS id_1'
            . ' FROM product_table p1_'
            . ' INNER JOIN user_table u0_ ON p1_.owner_id = u0_.id'
            . ' WHERE u0_.id = ?'
            . ' ORDER BY p1_.id DESC',
            [
                [
                    'id_0' => 1,
                    'id_1' => 20
                ],
                [
                    'id_0' => 1,
                    'id_1' => 10
                ]
            ],
            [1 => 1],
            [1 => \PDO::PARAM_INT]
        );
        $this->applyQueryExpectations($this->getDriverConnectionMock($this->em));

        $result = $this->serializer->serialize(
            $qb,
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'id'       => null,
                    'name'     => null,
                    'products' => [
                        'fields'   => 'id',
                        'order_by' => ['id' => 'DESC']
                    ]
                ]
            ]
        );

        $this->assertArrayEquals(
            [
                [
                    'id'       => 1,
                    'name'     => 'user_name',
                    'products' => [20, 10]
                ]
            ],
            $result
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testManyToManyBidirectionalWithManyToOne(): void
    {
        $qb = $this->em->getRepository(User::class)->createQueryBuilder('e')
            ->where('e.id = :id')
            ->setParameter('id', 1);

        $this->addQueryExpectation(
            'SELECT u0_.id AS id_0, u0_.name AS name_1'
            . ' FROM user_table u0_'
            . ' WHERE u0_.id = ?',
            [
                [
                    'id_0'   => 1,
                    'name_1' => 'user_name'
                ]
            ],
            [1 => 1],
            [1 => \PDO::PARAM_INT]
        );
        $this->addQueryExpectation(
            'SELECT u0_.id AS id_0,'
            . ' p1_.id AS id_1'
            . ' FROM product_table p1_'
            . ' INNER JOIN user_table u0_ ON p1_.owner_id = u0_.id'
            . ' WHERE u0_.id = ?',
            [
                [
                    'id_0' => 1,
                    'id_1' => 10
                ],
                [
                    'id_0' => 1,
                    'id_1' => 20
                ]
            ],
            [1 => 1],
            [1 => \PDO::PARAM_INT]
        );
        $this->addQueryExpectation(
            'SELECT p0_.id AS id_0, p0_.name AS name_1,'
            . ' c1_.name AS name_2'
            . ' FROM product_table p0_'
            . ' LEFT JOIN category_table c1_ ON p0_.category_name = c1_.name'
            . ' WHERE p0_.id IN (?, ?)',
            [
                [
                    'id_0'   => 10,
                    'name_1' => 'product_name1',
                    'name_2' => 'category_name1'
                ],
                [
                    'id_0'   => 20,
                    'name_1' => 'product_name2',
                    'name_2' => 'category_name2'
                ]
            ],
            [1 => 10, 2 => 20],
            [1 => \PDO::PARAM_INT, 2 => \PDO::PARAM_INT]
        );
        $this->applyQueryExpectations($this->getDriverConnectionMock($this->em));

        $result = $this->serializer->serialize(
            $qb,
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'id'       => null,
                    'name'     => null,
                    'products' => [
                        'exclusion_policy' => 'all',
                        'fields'           => [
                            'id'       => null,
                            'name'     => null,
                            'category' => [
                                'fields' => 'name'
                            ]
                        ]
                    ]
                ]
            ]
        );

        $this->assertArrayEquals(
            [
                [
                    'id'       => 1,
                    'name'     => 'user_name',
                    'products' => [
                        [
                            'id'       => 10,
                            'name'     => 'product_name1',
                            'category' => 'category_name1'
                        ],
                        [
                            'id'       => 20,
                            'name'     => 'product_name2',
                            'category' => 'category_name2'
                        ]
                    ]
                ]
            ],
            $result
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testManyToManyBidirectionalWithManyToMany(): void
    {
        $qb = $this->em->getRepository(User::class)->createQueryBuilder('e')
            ->where('e.id = :id')
            ->setParameter('id', 1);

        $this->addQueryExpectation(
            'SELECT u0_.id AS id_0, u0_.name AS name_1'
            . ' FROM user_table u0_'
            . ' WHERE u0_.id = ?',
            [
                [
                    'id_0'   => 1,
                    'name_1' => 'user_name'
                ]
            ],
            [1 => 1],
            [1 => \PDO::PARAM_INT]
        );
        $this->addQueryExpectation(
            'SELECT u0_.id AS id_0,'
            . ' p1_.id AS id_1'
            . ' FROM product_table p1_'
            . ' INNER JOIN user_table u0_ ON p1_.owner_id = u0_.id'
            . ' WHERE u0_.id = ?',
            [
                [
                    'id_0' => 1,
                    'id_1' => 10
                ],
                [
                    'id_0' => 1,
                    'id_1' => 20
                ]
            ],
            [1 => 1],
            [1 => \PDO::PARAM_INT]
        );
        $this->addQueryExpectation(
            'SELECT p0_.id AS id_0, p0_.name AS name_1'
            . ' FROM product_table p0_'
            . ' WHERE p0_.id IN (?, ?)',
            [
                [
                    'id_0'   => 10,
                    'name_1' => 'product_name1'
                ],
                [
                    'id_0'   => 20,
                    'name_1' => 'product_name2'
                ]
            ],
            [1 => 10, 2 => 20],
            [1 => \PDO::PARAM_INT, 2 => \PDO::PARAM_INT]
        );
        $this->addQueryExpectation(
            'SELECT p0_.id AS id_0, g1_.id AS id_1'
            . ' FROM product_table p0_'
            . ' INNER JOIN rel_product_to_group_table r2_ ON p0_.id = r2_.product_id'
            . ' INNER JOIN group_table g1_ ON g1_.id = r2_.product_group_id'
            . ' WHERE p0_.id IN (?, ?)',
            [
                [
                    'id_0' => 10,
                    'id_1' => 100
                ],
                [
                    'id_0' => 20,
                    'id_1' => 200
                ],
                [
                    'id_0' => 20,
                    'id_1' => 201
                ]
            ],
            [1 => 10, 2 => 20],
            [1 => \PDO::PARAM_INT, 2 => \PDO::PARAM_INT]
        );
        $this->applyQueryExpectations($this->getDriverConnectionMock($this->em));

        $result = $this->serializer->serialize(
            $qb,
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'id'       => null,
                    'name'     => null,
                    'products' => [
                        'exclusion_policy' => 'all',
                        'fields'           => [
                            'id'     => null,
                            'name'   => null,
                            'groups' => [
                                'fields' => 'id'
                            ]
                        ]
                    ]
                ]
            ]
        );

        $this->assertArrayEquals(
            [
                [
                    'id'       => 1,
                    'name'     => 'user_name',
                    'products' => [
                        [
                            'id'     => 10,
                            'name'   => 'product_name1',
                            'groups' => [100]
                        ],
                        [
                            'id'     => 20,
                            'name'   => 'product_name2',
                            'groups' => [200, 201]
                        ]
                    ]
                ]
            ],
            $result
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testManyToManyBidirectionalAndMasResultsAndOrderBy(): void
    {
        $qb = $this->em->getRepository(User::class)->createQueryBuilder('e')
            ->where('e.id = :id')
            ->setParameter('id', 1);

        $this->addQueryExpectation(
            'SELECT u0_.id AS id_0, u0_.name AS name_1'
            . ' FROM user_table u0_'
            . ' WHERE u0_.id = ?',
            [
                [
                    'id_0'   => 1,
                    'name_1' => 'user_name'
                ]
            ],
            [1 => 1],
            [1 => \PDO::PARAM_INT]
        );
        $this->addQueryExpectation(
            'SELECT u0_.id AS id_0, p1_.id AS id_1'
            . ' FROM product_table p1_'
            . ' INNER JOIN user_table u0_ ON p1_.owner_id = u0_.id'
            . ' WHERE u0_.id = ?'
            . ' ORDER BY p1_.name DESC'
            . ' LIMIT 10',
            [
                [
                    'id_0' => 1,
                    'id_1' => 10
                ],
                [
                    'id_0' => 1,
                    'id_1' => 20
                ]
            ],
            [1 => 1],
            [1 => \PDO::PARAM_INT]
        );
        $this->addQueryExpectation(
            'SELECT p0_.id AS id_0, p0_.name AS name_1'
            . ' FROM product_table p0_'
            . ' WHERE p0_.id IN (?, ?)',
            [
                [
                    'id_0'   => 10,
                    'name_1' => 'product_name1'
                ],
                [
                    'id_0'   => 20,
                    'name_1' => 'product_name2'
                ]
            ],
            [1 => 10, 2 => 20],
            [1 => \PDO::PARAM_INT, 2 => \PDO::PARAM_INT]
        );
        $this->applyQueryExpectations($this->getDriverConnectionMock($this->em));

        $result = $this->serializer->serialize(
            $qb,
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'id'       => null,
                    'name'     => null,
                    'products' => [
                        'exclusion_policy' => 'all',
                        'max_results'      => 10,
                        'order_by'         => ['name' => 'DESC'],
                        'fields'           => [
                            'id'   => null,
                            'name' => null
                        ]
                    ]
                ]
            ]
        );

        $this->assertArrayEquals(
            [
                [
                    'id'       => 1,
                    'name'     => 'user_name',
                    'products' => [
                        [
                            'id'   => 10,
                            'name' => 'product_name1'
                        ],
                        [
                            'id'   => 20,
                            'name' => 'product_name2'
                        ]
                    ]
                ]
            ],
            $result
        );
    }

    public function testSerializeWithFieldsFilterAndEnabledToManyAssociation(): void
    {
        $qb = $this->em->getRepository(Product::class)->createQueryBuilder('e')
            ->leftJoin('e.owner', 'user')
            ->where('e.id = :id')
            ->setParameter('id', 1);

        $this->addQueryExpectation(
            'SELECT p0_.id AS id_0, p0_.name AS name_1,'
            . ' u1_.id AS id_2'
            . ' FROM product_table p0_'
            . ' LEFT JOIN user_table u1_ ON p0_.owner_id = u1_.id'
            . ' WHERE p0_.id = ?',
            [
                [
                    'id_0'   => 1,
                    'name_1' => 'product_name',
                    'id_2'   => 10
                ]
            ],
            [1 => 1],
            [1 => \PDO::PARAM_INT]
        );
        $this->addQueryExpectation(
            'SELECT p0_.id AS id_0, g1_.id AS id_1'
            . ' FROM product_table p0_'
            . ' INNER JOIN rel_product_to_group_table r2_ ON p0_.id = r2_.product_id'
            . ' INNER JOIN group_table g1_ ON g1_.id = r2_.product_group_id'
            . ' WHERE p0_.id = ?',
            [
                [
                    'id_0' => 1,
                    'id_1' => 1
                ],
                [
                    'id_0' => 1,
                    'id_1' => 2
                ]
            ],
            [1 => 1],
            [1 => \PDO::PARAM_INT]
        );
        $this->applyQueryExpectations($this->getDriverConnectionMock($this->em));

        $this->serializer->setFieldFilter($this->getFieldFilter([
            'name'  => false,
            'owner' => true
        ]));

        $result = $this->serializer->serialize(
            $qb,
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'id'     => null,
                    'name'   => null,
                    'owner'  => [
                        'fields' => 'id'
                    ],
                    'groups' => [
                        'exclusion_policy' => 'all',
                        'fields'           => [
                            'id' => null
                        ]
                    ]
                ]
            ]
        );

        $this->assertArrayEquals(
            [
                [
                    'groups' => [
                        ['id' => 1],
                        ['id' => 2]
                    ],
                    'id'     => 1,
                    'name'   => null
                ]
            ],
            $result
        );
    }

    public function testSerializeWithFieldsFilterAndDisabledToManyAssociation(): void
    {
        $qb = $this->em->getRepository(Product::class)->createQueryBuilder('e')
            ->leftJoin('e.owner', 'user')
            ->where('e.id = :id')
            ->setParameter('id', 1);

        $conn = $this->getDriverConnectionMock($this->em);

        $this->setQueryExpectation(
            $conn,
            'SELECT p0_.id AS id_0, p0_.name AS name_1,'
            . ' u1_.id AS id_2'
            . ' FROM product_table p0_'
            . ' LEFT JOIN user_table u1_ ON p0_.owner_id = u1_.id'
            . ' WHERE p0_.id = ?',
            [
                [
                    'id_0'   => 1,
                    'name_1' => 'product_name',
                    'id_2'   => 10
                ]
            ],
            [1 => 1],
            [1 => \PDO::PARAM_INT]
        );

        $this->serializer->setFieldFilter($this->getFieldFilter([
            'name'   => false,
            'groups' => false,
            'owner'  => true
        ]));

        $result = $this->serializer->serialize(
            $qb,
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'id'     => null,
                    'name'   => null,
                    'owner'  => [
                        'fields' => 'id'
                    ],
                    'groups' => [
                        'exclusion_policy' => 'all',
                        'fields'           => [
                            'id' => null
                        ]
                    ]
                ]
            ]
        );

        $this->assertArrayEquals(
            [
                [
                    'id'   => 1,
                    'name' => null
                ]
            ],
            $result
        );
    }
}
