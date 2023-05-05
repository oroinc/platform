<?php

namespace Oro\Component\EntitySerializer\Tests\Unit;

use Oro\Component\EntitySerializer\Tests\Unit\Fixtures\Entity\Group;
use Oro\Component\EntitySerializer\Tests\Unit\Fixtures\Entity\User;

class HasMoreTest extends EntitySerializerTestCase
{
    public function testQueryHasMaxResultsButThereIsNoHasMoreOptionInConfig(): void
    {
        $qb = $this->em->getRepository(Group::class)->createQueryBuilder('e')
            ->setMaxResults(2);

        $this->setQueryExpectation(
            $this->getDriverConnectionMock($this->em),
            'SELECT g0_.id AS id_0, g0_.label AS label_1 FROM group_table g0_ LIMIT 2',
            [
                ['id_0' => 1, 'label_1' => 'item1'],
                ['id_0' => 2, 'label_1' => 'item2']
            ]
        );

        $result = $this->serializer->serialize(
            $qb,
            [
                'fields' => [
                    'name'        => ['exclude' => true],
                    'public'      => ['exclude' => true],
                    'isException' => ['exclude' => true]
                ]
            ]
        );

        $this->assertArrayEquals(
            [
                0 => ['id' => 1, 'label' => 'item1'],
                1 => ['id' => 2, 'label' => 'item2']
            ],
            $result
        );
    }

    public function testHasMoreWhenQueryHasMaxResultsAndThereAreMoreRecords(): void
    {
        $qb = $this->em->getRepository(Group::class)->createQueryBuilder('e')
            ->setMaxResults(2);

        $this->setQueryExpectation(
            $this->getDriverConnectionMock($this->em),
            'SELECT g0_.id AS id_0, g0_.label AS label_1 FROM group_table g0_ LIMIT 3',
            [
                ['id_0' => 1, 'label_1' => 'item1'],
                ['id_0' => 2, 'label_1' => 'item2'],
                ['id_0' => 3, 'label_1' => 'item3']
            ]
        );

        $result = $this->serializer->serialize(
            $qb,
            [
                'has_more' => true,
                'fields'   => [
                    'name'        => ['exclude' => true],
                    'public'      => ['exclude' => true],
                    'isException' => ['exclude' => true]
                ]
            ]
        );

        $this->assertArrayEquals(
            [
                0   => ['id' => 1, 'label' => 'item1'],
                1   => ['id' => 2, 'label' => 'item2'],
                '_' => ['has_more' => true]
            ],
            $result
        );
    }

    public function testHasMoreWhenQueryHasMaxResultsAndThereAreNoMoreRecords(): void
    {
        $qb = $this->em->getRepository(Group::class)->createQueryBuilder('e')
            ->setMaxResults(2);

        $this->setQueryExpectation(
            $this->getDriverConnectionMock($this->em),
            'SELECT g0_.id AS id_0, g0_.label AS label_1 FROM group_table g0_ LIMIT 3',
            [
                ['id_0' => 1, 'label_1' => 'item1'],
                ['id_0' => 2, 'label_1' => 'item2']
            ]
        );

        $result = $this->serializer->serialize(
            $qb,
            [
                'has_more' => true,
                'fields'   => [
                    'name'        => ['exclude' => true],
                    'public'      => ['exclude' => true],
                    'isException' => ['exclude' => true]
                ]
            ]
        );

        $this->assertArrayEquals(
            [
                0 => ['id' => 1, 'label' => 'item1'],
                1 => ['id' => 2, 'label' => 'item2']
            ],
            $result
        );
    }

    public function testHasMoreWhenQueryDoesNotHaveMaxResults(): void
    {
        $qb = $this->em->getRepository(Group::class)->createQueryBuilder('e');

        $this->setQueryExpectation(
            $this->getDriverConnectionMock($this->em),
            'SELECT g0_.id AS id_0, g0_.label AS label_1 FROM group_table g0_',
            [
                ['id_0' => 1, 'label_1' => 'item1'],
                ['id_0' => 2, 'label_1' => 'item2']
            ]
        );

        $result = $this->serializer->serialize(
            $qb,
            [
                'has_more' => true,
                'fields'   => [
                    'name'        => ['exclude' => true],
                    'public'      => ['exclude' => true],
                    'isException' => ['exclude' => true]
                ]
            ]
        );

        $this->assertArrayEquals(
            [
                0 => ['id' => 1, 'label' => 'item1'],
                1 => ['id' => 2, 'label' => 'item2']
            ],
            $result
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testHasMoreWhenToManyAssociationQueryHasMaxResultsAndThereAreMoreRecords(): void
    {
        $qb = $this->em->getRepository(User::class)->createQueryBuilder('e')
            ->where('e.id = :id')
            ->where('e.id IN (:ids)')
            ->setParameter('ids', [123, 456, 789]);

        $this->addQueryExpectation(
            'SELECT u0_.id AS id_0, u0_.name AS name_1'
            . ' FROM user_table u0_'
            . ' WHERE u0_.id IN (?, ?, ?)',
            [
                ['id_0' => 123, 'name_1' => 'user123'],
                ['id_0' => 456, 'name_1' => 'user456'],
                ['id_0' => 789, 'name_1' => 'user789']
            ],
            [1 => 123, 2 => 456, 3 => 789],
            [1 => \PDO::PARAM_INT, 2 => \PDO::PARAM_INT, 3 => \PDO::PARAM_INT]
        );
        $this->addQueryExpectation(
            'SELECT entity.id_0 AS entityId, entity.id_1 AS relatedEntityId'
            . ' FROM (('
            . 'SELECT u0_.id AS id_0, g1_.id AS id_1'
            . ' FROM user_table u0_'
            . ' INNER JOIN rel_user_to_group_table r2_ ON u0_.id = r2_.user_id'
            . ' INNER JOIN group_table g1_ ON g1_.id = r2_.user_group_id'
            . ' WHERE u0_.id = 123 LIMIT 3'
            . ') UNION ALL ('
            . 'SELECT u0_.id AS id_0, g1_.id AS id_1'
            . ' FROM user_table u0_'
            . ' INNER JOIN rel_user_to_group_table r2_ ON u0_.id = r2_.user_id'
            . ' INNER JOIN group_table g1_ ON g1_.id = r2_.user_group_id'
            . ' WHERE u0_.id = 456 LIMIT 3'
            . ') UNION ALL ('
            . 'SELECT u0_.id AS id_0, g1_.id AS id_1'
            . ' FROM user_table u0_'
            . ' INNER JOIN rel_user_to_group_table r2_ ON u0_.id = r2_.user_id'
            . ' INNER JOIN group_table g1_ ON g1_.id = r2_.user_group_id'
            . ' WHERE u0_.id = 789 LIMIT 3'
            . ')) entity',
            [
                ['entityId' => 123, 'relatedEntityId' => 11],
                ['entityId' => 123, 'relatedEntityId' => 12],
                ['entityId' => 123, 'relatedEntityId' => 13],
                ['entityId' => 456, 'relatedEntityId' => 21],
                ['entityId' => 456, 'relatedEntityId' => 22],
                ['entityId' => 789, 'relatedEntityId' => 31],
                ['entityId' => 789, 'relatedEntityId' => 32],
                ['entityId' => 789, 'relatedEntityId' => 33]
            ]
        );
        $this->addQueryExpectation(
            'SELECT g0_.id AS id_0, g0_.label AS label_1 FROM group_table g0_ WHERE g0_.id IN (?, ?, ?, ?, ?, ?)',
            [
                ['id_0' => 11, 'label_1' => 'group11'],
                ['id_0' => 12, 'label_1' => 'group12'],
                ['id_0' => 21, 'label_1' => 'group21'],
                ['id_0' => 22, 'label_1' => 'group22'],
                ['id_0' => 31, 'label_1' => 'group31'],
                ['id_0' => 32, 'label_1' => 'group32']
            ],
            [1 => 11, 2 => 12, 3 => 21, 4 => 22, 5 => 31, 6 => 32],
            [
                1 => \PDO::PARAM_INT,
                2 => \PDO::PARAM_INT,
                3 => \PDO::PARAM_INT,
                4 => \PDO::PARAM_INT,
                5 => \PDO::PARAM_INT,
                6 => \PDO::PARAM_INT
            ]
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
                        'has_more'         => true,
                        'max_results'      => 2,
                        'exclusion_policy' => 'all',
                        'fields'           => [
                            'id'    => null,
                            'label' => null
                        ]
                    ]
                ]
            ]
        );

        $this->assertArrayEquals(
            [
                [
                    'id'     => 123,
                    'name'   => 'user123',
                    'groups' => [
                        0   => ['id' => 11, 'label' => 'group11'],
                        1   => ['id' => 12, 'label' => 'group12'],
                        '_' => ['has_more' => true]
                    ]
                ],
                [
                    'id'     => 456,
                    'name'   => 'user456',
                    'groups' => [
                        0 => ['id' => 21, 'label' => 'group21'],
                        1 => ['id' => 22, 'label' => 'group22']
                    ]
                ],
                [
                    'id'     => 789,
                    'name'   => 'user789',
                    'groups' => [
                        0   => ['id' => 31, 'label' => 'group31'],
                        1   => ['id' => 32, 'label' => 'group32'],
                        '_' => ['has_more' => true]
                    ]
                ]
            ],
            $result
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testHasMoreWhenToManyCollapsedAssociationQueryHasMaxResultsAndThereAreMoreRecords(): void
    {
        $qb = $this->em->getRepository(User::class)->createQueryBuilder('e')
            ->where('e.id = :id')
            ->where('e.id IN (:ids)')
            ->setParameter('ids', [123, 456, 789]);

        $this->addQueryExpectation(
            'SELECT u0_.id AS id_0, u0_.name AS name_1'
            . ' FROM user_table u0_'
            . ' WHERE u0_.id IN (?, ?, ?)',
            [
                ['id_0' => 123, 'name_1' => 'user123'],
                ['id_0' => 456, 'name_1' => 'user456'],
                ['id_0' => 789, 'name_1' => 'user789']
            ],
            [1 => 123, 2 => 456, 3 => 789],
            [1 => \PDO::PARAM_INT, 2 => \PDO::PARAM_INT, 3 => \PDO::PARAM_INT]
        );
        $this->addQueryExpectation(
            'SELECT entity.id_0 AS entityId, entity.id_1 AS relatedEntityId'
            . ' FROM (('
            . 'SELECT u0_.id AS id_0, g1_.id AS id_1'
            . ' FROM user_table u0_'
            . ' INNER JOIN rel_user_to_group_table r2_ ON u0_.id = r2_.user_id'
            . ' INNER JOIN group_table g1_ ON g1_.id = r2_.user_group_id'
            . ' WHERE u0_.id = 123 LIMIT 3'
            . ') UNION ALL ('
            . 'SELECT u0_.id AS id_0, g1_.id AS id_1'
            . ' FROM user_table u0_'
            . ' INNER JOIN rel_user_to_group_table r2_ ON u0_.id = r2_.user_id'
            . ' INNER JOIN group_table g1_ ON g1_.id = r2_.user_group_id'
            . ' WHERE u0_.id = 456 LIMIT 3'
            . ') UNION ALL ('
            . 'SELECT u0_.id AS id_0, g1_.id AS id_1'
            . ' FROM user_table u0_'
            . ' INNER JOIN rel_user_to_group_table r2_ ON u0_.id = r2_.user_id'
            . ' INNER JOIN group_table g1_ ON g1_.id = r2_.user_group_id'
            . ' WHERE u0_.id = 789 LIMIT 3'
            . ')) entity',
            [
                ['entityId' => 123, 'relatedEntityId' => 11],
                ['entityId' => 123, 'relatedEntityId' => 12],
                ['entityId' => 123, 'relatedEntityId' => 13],
                ['entityId' => 456, 'relatedEntityId' => 21],
                ['entityId' => 456, 'relatedEntityId' => 22],
                ['entityId' => 789, 'relatedEntityId' => 31],
                ['entityId' => 789, 'relatedEntityId' => 32],
                ['entityId' => 789, 'relatedEntityId' => 33]
            ]
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
                        'has_more'         => true,
                        'max_results'      => 2,
                        'exclusion_policy' => 'all',
                        'collapse'         => true,
                        'fields'           => ['id' => null]
                    ]
                ]
            ]
        );

        $this->assertArrayEquals(
            [
                [
                    'id'     => 123,
                    'name'   => 'user123',
                    'groups' => [
                        0   => 11,
                        1   => 12,
                        '_' => ['has_more' => true]
                    ]
                ],
                [
                    'id'     => 456,
                    'name'   => 'user456',
                    'groups' => [
                        0 => 21,
                        1 => 22
                    ]
                ],
                [
                    'id'     => 789,
                    'name'   => 'user789',
                    'groups' => [
                        0   => 31,
                        1   => 32,
                        '_' => ['has_more' => true]
                    ]
                ]
            ],
            $result
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testHasMoreWhenToManyAssociationQueryHasMaxResultsAndThereAreNoMoreRecords(): void
    {
        $qb = $this->em->getRepository(User::class)->createQueryBuilder('e')
            ->where('e.id = :id')
            ->where('e.id IN (:ids)')
            ->setParameter('ids', [123, 456]);

        $this->addQueryExpectation(
            'SELECT u0_.id AS id_0, u0_.name AS name_1'
            . ' FROM user_table u0_'
            . ' WHERE u0_.id IN (?, ?)',
            [
                ['id_0' => 123, 'name_1' => 'user123'],
                ['id_0' => 456, 'name_1' => 'user456']
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
            . ' WHERE u0_.id = 123 LIMIT 3'
            . ') UNION ALL ('
            . 'SELECT u0_.id AS id_0, g1_.id AS id_1'
            . ' FROM user_table u0_'
            . ' INNER JOIN rel_user_to_group_table r2_ ON u0_.id = r2_.user_id'
            . ' INNER JOIN group_table g1_ ON g1_.id = r2_.user_group_id'
            . ' WHERE u0_.id = 456 LIMIT 3'
            . ')) entity',
            [
                ['entityId' => 123, 'relatedEntityId' => 11],
                ['entityId' => 123, 'relatedEntityId' => 12],
                ['entityId' => 456, 'relatedEntityId' => 21],
                ['entityId' => 456, 'relatedEntityId' => 22]
            ]
        );
        $this->addQueryExpectation(
            'SELECT g0_.id AS id_0, g0_.label AS label_1 FROM group_table g0_ WHERE g0_.id IN (?, ?, ?, ?)',
            [
                ['id_0' => 11, 'label_1' => 'group11'],
                ['id_0' => 12, 'label_1' => 'group12'],
                ['id_0' => 21, 'label_1' => 'group21'],
                ['id_0' => 22, 'label_1' => 'group22']
            ],
            [1 => 11, 2 => 12, 3 => 21, 4 => 22],
            [
                1 => \PDO::PARAM_INT,
                2 => \PDO::PARAM_INT,
                3 => \PDO::PARAM_INT,
                4 => \PDO::PARAM_INT
            ]
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
                        'has_more'         => true,
                        'max_results'      => 2,
                        'exclusion_policy' => 'all',
                        'fields'           => [
                            'id'    => null,
                            'label' => null
                        ]
                    ]
                ]
            ]
        );

        $this->assertArrayEquals(
            [
                [
                    'id'     => 123,
                    'name'   => 'user123',
                    'groups' => [
                        0 => ['id' => 11, 'label' => 'group11'],
                        1 => ['id' => 12, 'label' => 'group12']
                    ]
                ],
                [
                    'id'     => 456,
                    'name'   => 'user456',
                    'groups' => [
                        0 => ['id' => 21, 'label' => 'group21'],
                        1 => ['id' => 22, 'label' => 'group22']
                    ]
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
                . ' WHERE u0_.id = %d LIMIT 11', $i);
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
                        'has_more'         => true,
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

    public function testHasMoreWhenToManyAssociationQueryDoesNotHaveMaxResults(): void
    {
        $qb = $this->em->getRepository(User::class)->createQueryBuilder('e')
            ->where('e.id = :id')
            ->where('e.id IN (:ids)')
            ->setParameter('ids', [123, 456]);

        $this->addQueryExpectation(
            'SELECT u0_.id AS id_0, u0_.name AS name_1'
            . ' FROM user_table u0_'
            . ' WHERE u0_.id IN (?, ?)',
            [
                ['id_0' => 123, 'name_1' => 'user123'],
                ['id_0' => 456, 'name_1' => 'user456']
            ],
            [1 => 123, 2 => 456],
            [1 => \PDO::PARAM_INT, 2 => \PDO::PARAM_INT]
        );
        $this->addQueryExpectation(
            'SELECT u0_.id AS id_0, g1_.id AS id_1, g1_.label AS label_2'
            . ' FROM user_table u0_'
            . ' INNER JOIN rel_user_to_group_table r2_ ON u0_.id = r2_.user_id'
            . ' INNER JOIN group_table g1_ ON g1_.id = r2_.user_group_id'
            . ' WHERE u0_.id IN (?, ?)',
            [
                ['id_0' => 123, 'id_1' => 11, 'label_2' => 'group11'],
                ['id_0' => 123, 'id_1' => 12, 'label_2' => 'group12'],
                ['id_0' => 456, 'id_1' => 21, 'label_2' => 'group21'],
                ['id_0' => 456, 'id_1' => 22, 'label_2' => 'group22']
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
                        'has_more'         => true,
                        'exclusion_policy' => 'all',
                        'fields'           => [
                            'id'    => null,
                            'label' => null
                        ]
                    ]
                ]
            ]
        );

        $this->assertArrayEquals(
            [
                [
                    'id'     => 123,
                    'name'   => 'user123',
                    'groups' => [
                        0 => ['id' => 11, 'label' => 'group11'],
                        1 => ['id' => 12, 'label' => 'group12']
                    ]
                ],
                [
                    'id'     => 456,
                    'name'   => 'user456',
                    'groups' => [
                        0 => ['id' => 21, 'label' => 'group21'],
                        1 => ['id' => 22, 'label' => 'group22']
                    ]
                ]
            ],
            $result
        );
    }
}
