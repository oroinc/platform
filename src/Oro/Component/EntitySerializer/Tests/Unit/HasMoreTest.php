<?php

namespace Oro\Component\EntitySerializer\Tests\Unit;

class HasMoreTest extends EntitySerializerTestCase
{
    public function testQueryHasMaxResultsButThereIsNoHasMoreOptionInConfig()
    {
        $qb = $this->em->getRepository('Test:Group')->createQueryBuilder('e')
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

    public function testHasMoreWhenQueryHasMaxResultsAndThereAreMoreRecords()
    {
        $qb = $this->em->getRepository('Test:Group')->createQueryBuilder('e')
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

    public function testHasMoreWhenQueryHasMaxResultsAndThereAreNoMoreRecords()
    {
        $qb = $this->em->getRepository('Test:Group')->createQueryBuilder('e')
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

    public function testHasMoreWhenQueryDoesNotHaveMaxResults()
    {
        $qb = $this->em->getRepository('Test:Group')->createQueryBuilder('e');

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
    public function testHasMoreWhenToManyAssociationQueryHasMaxResultsAndThereAreMoreRecords()
    {
        $qb = $this->em->getRepository('Test:User')->createQueryBuilder('e')
            ->where('e.id = :id')
            ->where('e.id IN (:ids)')
            ->setParameter('ids', [123, 456, 789]);

        $conn = $this->getDriverConnectionMock($this->em);
        $this->setQueryExpectationAt(
            $conn,
            0,
            'SELECT u0_.id AS id_0, u0_.name AS name_1, u0_.category_name AS category_name_2'
            . ' FROM user_table u0_'
            . ' WHERE u0_.id IN (?, ?, ?)',
            [
                ['id_0' => 123, 'name_1' => 'user123', 'category_name_2' => null],
                ['id_0' => 456, 'name_1' => 'user456', 'category_name_2' => null],
                ['id_0' => 789, 'name_1' => 'user789', 'category_name_2' => null]
            ],
            [1 => 123, 2 => 456, 3 => 789],
            [1 => \PDO::PARAM_INT, 2 => \PDO::PARAM_INT, 3 => \PDO::PARAM_INT]
        );
        $this->setQueryExpectationAt(
            $conn,
            1,
            'SELECT entity.id_0 AS entityId, entity.id_1 AS relatedEntityId'
            . ' FROM ('
            . '(SELECT u0_.id AS id_0, g1_.id AS id_1 FROM group_table g1_'
            . ' INNER JOIN user_table u0_ ON (EXISTS ('
            . 'SELECT 1 FROM rel_user_to_group_table r2_'
            . ' INNER JOIN group_table g3_ ON r2_.user_group_id = g3_.id'
            . ' WHERE r2_.user_id = u0_.id AND g3_.id IN (g1_.id)'
            . ')) WHERE u0_.id = 123 LIMIT 3)'
            . ' UNION ALL'
            . ' (SELECT u0_.id AS id_0, g1_.id AS id_1 FROM group_table g1_'
            . ' INNER JOIN user_table u0_ ON (EXISTS ('
            . 'SELECT 1 FROM rel_user_to_group_table r2_'
            . ' INNER JOIN group_table g3_ ON r2_.user_group_id = g3_.id'
            . ' WHERE r2_.user_id = u0_.id AND g3_.id IN (g1_.id)'
            . ')) WHERE u0_.id = 456 LIMIT 3)'
            . ' UNION ALL'
            . ' (SELECT u0_.id AS id_0, g1_.id AS id_1 FROM group_table g1_'
            . ' INNER JOIN user_table u0_ ON (EXISTS ('
            . 'SELECT 1 FROM rel_user_to_group_table r2_'
            . ' INNER JOIN group_table g3_ ON r2_.user_group_id = g3_.id'
            . ' WHERE r2_.user_id = u0_.id AND g3_.id IN (g1_.id)'
            . ')) WHERE u0_.id = 789 LIMIT 3)'
            . ') entity',
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
        $this->setQueryExpectationAt(
            $conn,
            2,
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
    public function testHasMoreWhenToManyCollapsedAssociationQueryHasMaxResultsAndThereAreMoreRecords()
    {
        $qb = $this->em->getRepository('Test:User')->createQueryBuilder('e')
            ->where('e.id = :id')
            ->where('e.id IN (:ids)')
            ->setParameter('ids', [123, 456, 789]);

        $conn = $this->getDriverConnectionMock($this->em);
        $this->setQueryExpectationAt(
            $conn,
            0,
            'SELECT u0_.id AS id_0, u0_.name AS name_1, u0_.category_name AS category_name_2'
            . ' FROM user_table u0_'
            . ' WHERE u0_.id IN (?, ?, ?)',
            [
                ['id_0' => 123, 'name_1' => 'user123', 'category_name_2' => null],
                ['id_0' => 456, 'name_1' => 'user456', 'category_name_2' => null],
                ['id_0' => 789, 'name_1' => 'user789', 'category_name_2' => null]
            ],
            [1 => 123, 2 => 456, 3 => 789],
            [1 => \PDO::PARAM_INT, 2 => \PDO::PARAM_INT, 3 => \PDO::PARAM_INT]
        );
        $this->setQueryExpectationAt(
            $conn,
            1,
            'SELECT entity.id_0 AS entityId, entity.id_1 AS relatedEntityId'
            . ' FROM ('
            . '(SELECT u0_.id AS id_0, g1_.id AS id_1 FROM group_table g1_'
            . ' INNER JOIN user_table u0_ ON (EXISTS ('
            . 'SELECT 1 FROM rel_user_to_group_table r2_'
            . ' INNER JOIN group_table g3_ ON r2_.user_group_id = g3_.id'
            . ' WHERE r2_.user_id = u0_.id AND g3_.id IN (g1_.id)'
            . ')) WHERE u0_.id = 123 LIMIT 3)'
            . ' UNION ALL'
            . ' (SELECT u0_.id AS id_0, g1_.id AS id_1 FROM group_table g1_'
            . ' INNER JOIN user_table u0_ ON (EXISTS ('
            . 'SELECT 1 FROM rel_user_to_group_table r2_'
            . ' INNER JOIN group_table g3_ ON r2_.user_group_id = g3_.id'
            . ' WHERE r2_.user_id = u0_.id AND g3_.id IN (g1_.id)'
            . ')) WHERE u0_.id = 456 LIMIT 3)'
            . ' UNION ALL'
            . ' (SELECT u0_.id AS id_0, g1_.id AS id_1 FROM group_table g1_'
            . ' INNER JOIN user_table u0_ ON (EXISTS ('
            . 'SELECT 1 FROM rel_user_to_group_table r2_'
            . ' INNER JOIN group_table g3_ ON r2_.user_group_id = g3_.id'
            . ' WHERE r2_.user_id = u0_.id AND g3_.id IN (g1_.id)'
            . ')) WHERE u0_.id = 789 LIMIT 3)'
            . ') entity',
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
    public function testHasMoreWhenToManyAssociationQueryHasMaxResultsAndThereAreNoMoreRecords()
    {
        $qb = $this->em->getRepository('Test:User')->createQueryBuilder('e')
            ->where('e.id = :id')
            ->where('e.id IN (:ids)')
            ->setParameter('ids', [123, 456]);

        $conn = $this->getDriverConnectionMock($this->em);
        $this->setQueryExpectationAt(
            $conn,
            0,
            'SELECT u0_.id AS id_0, u0_.name AS name_1, u0_.category_name AS category_name_2'
            . ' FROM user_table u0_'
            . ' WHERE u0_.id IN (?, ?)',
            [
                ['id_0' => 123, 'name_1' => 'user123', 'category_name_2' => null],
                ['id_0' => 456, 'name_1' => 'user456', 'category_name_2' => null]
            ],
            [1 => 123, 2 => 456],
            [1 => \PDO::PARAM_INT, 2 => \PDO::PARAM_INT]
        );
        $this->setQueryExpectationAt(
            $conn,
            1,
            'SELECT entity.id_0 AS entityId, entity.id_1 AS relatedEntityId'
            . ' FROM ('
            . '(SELECT u0_.id AS id_0, g1_.id AS id_1 FROM group_table g1_'
            . ' INNER JOIN user_table u0_ ON (EXISTS ('
            . 'SELECT 1 FROM rel_user_to_group_table r2_'
            . ' INNER JOIN group_table g3_ ON r2_.user_group_id = g3_.id'
            . ' WHERE r2_.user_id = u0_.id AND g3_.id IN (g1_.id)'
            . ')) WHERE u0_.id = 123 LIMIT 3)'
            . ' UNION ALL'
            . ' (SELECT u0_.id AS id_0, g1_.id AS id_1 FROM group_table g1_'
            . ' INNER JOIN user_table u0_ ON (EXISTS ('
            . 'SELECT 1 FROM rel_user_to_group_table r2_'
            . ' INNER JOIN group_table g3_ ON r2_.user_group_id = g3_.id'
            . ' WHERE r2_.user_id = u0_.id AND g3_.id IN (g1_.id)'
            . ')) WHERE u0_.id = 456 LIMIT 3)'
            . ') entity',
            [
                ['entityId' => 123, 'relatedEntityId' => 11],
                ['entityId' => 123, 'relatedEntityId' => 12],
                ['entityId' => 456, 'relatedEntityId' => 21],
                ['entityId' => 456, 'relatedEntityId' => 22]
            ]
        );
        $this->setQueryExpectationAt(
            $conn,
            2,
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

    public function testHasMoreWhenToManyAssociationQueryDoesNotHaveMaxResults()
    {
        $qb = $this->em->getRepository('Test:User')->createQueryBuilder('e')
            ->where('e.id = :id')
            ->where('e.id IN (:ids)')
            ->setParameter('ids', [123, 456]);

        $conn = $this->getDriverConnectionMock($this->em);
        $this->setQueryExpectationAt(
            $conn,
            0,
            'SELECT u0_.id AS id_0, u0_.name AS name_1, u0_.category_name AS category_name_2'
            . ' FROM user_table u0_'
            . ' WHERE u0_.id IN (?, ?)',
            [
                ['id_0' => 123, 'name_1' => 'user123', 'category_name_2' => null],
                ['id_0' => 456, 'name_1' => 'user456', 'category_name_2' => null]
            ],
            [1 => 123, 2 => 456],
            [1 => \PDO::PARAM_INT, 2 => \PDO::PARAM_INT]
        );
        $this->setQueryExpectationAt(
            $conn,
            1,
            'SELECT u0_.id AS id_0, g1_.id AS id_1, g1_.label AS label_2'
            . ' FROM group_table g1_'
            . ' INNER JOIN user_table u0_ ON (EXISTS ('
            . 'SELECT 1 FROM rel_user_to_group_table r2_'
            . ' INNER JOIN group_table g3_ ON r2_.user_group_id = g3_.id'
            . ' WHERE r2_.user_id = u0_.id AND g3_.id IN (g1_.id)'
            . '))'
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
