<?php

namespace Oro\Component\EntitySerializer\Tests\Unit;

class ToManyEntitySerializerTest extends EntitySerializerTestCase
{
    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testManyToManyUnidirectional()
    {
        $qb = $this->em->getRepository('Test:User')->createQueryBuilder('e')
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
                [
                    'id_0'            => 123,
                    'name_1'          => 'user_name1',
                    'category_name_2' => 'category_name1',
                ],
                [
                    'id_0'            => 456,
                    'name_1'          => 'user_name2',
                    'category_name_2' => 'category_name2',
                ]
            ],
            [1 => 123, 2 => 456],
            [1 => \PDO::PARAM_INT, 2 => \PDO::PARAM_INT]
        );

        $this->setQueryExpectationAt(
            $conn,
            1,
            'SELECT u0_.id AS id_0,'
            . ' g1_.id AS id_1, g1_.name AS name_2, g1_.label AS label_3, g1_.public AS public_4'
            . ' FROM group_table g1_'
            . ' INNER JOIN user_table u0_ ON (EXISTS ('
            . 'SELECT 1 FROM rel_user_to_group_table r2_'
            . ' INNER JOIN group_table g3_ ON r2_.user_group_id = g3_.id'
            . ' WHERE r2_.user_id = u0_.id AND g3_.id IN (g1_.id)))'
            . ' WHERE u0_.id IN (?, ?)',
            [
                [
                    'id_0'     => 123,
                    'id_1'     => 10,
                    'name_2'   => 'group_name1',
                    'label_3'  => 'group_label1',
                    'public_4' => 0,
                ],
                [
                    'id_0'     => 123,
                    'id_1'     => 20,
                    'name_2'   => 'group_name2',
                    'label_3'  => 'group_label2',
                    'public_4' => true,
                ],
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
                        'fields' => [
                            'isException' => [
                                'exclude' => true
                            ]
                        ]
                    ],
                ],
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
                            'public' => false,
                        ],
                        [
                            'id'     => 20,
                            'name'   => 'group_name2',
                            'label'  => 'group_label2',
                            'public' => true,
                        ],
                    ],
                ],
                [
                    'id'     => 456,
                    'name'   => 'user_name2',
                    'groups' => [],
                ]
            ],
            $result
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testManyToManyUnidirectionalWithSubQueryLimit()
    {
        $qb = $this->em->getRepository('Test:User')->createQueryBuilder('e')
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
                [
                    'id_0'            => 123,
                    'name_1'          => 'user_name1',
                    'category_name_2' => 'category_name1',
                ],
                [
                    'id_0'            => 456,
                    'name_1'          => 'user_name2',
                    'category_name_2' => 'category_name2',
                ]
            ],
            [1 => 123, 2 => 456],
            [1 => \PDO::PARAM_INT, 2 => \PDO::PARAM_INT]
        );

        $this->setQueryExpectationAt(
            $conn,
            1,
            'SELECT entity.id_0 AS entityId, entity.id_1 AS relatedEntityId'
            . ' FROM ('
            . '(SELECT u0_.id AS id_0, g1_.id AS id_1'
            . ' FROM group_table g1_'
            . ' INNER JOIN user_table u0_ ON (EXISTS ('
            . 'SELECT 1 FROM rel_user_to_group_table r2_'
            . ' INNER JOIN group_table g3_ ON r2_.user_group_id = g3_.id'
            . ' WHERE r2_.user_id = u0_.id AND g3_.id IN (g1_.id)'
            . '))'
            . ' WHERE u0_.id = 123 LIMIT 10)'
            . ' UNION ALL'
            . ' (SELECT u0_.id AS id_0, g1_.id AS id_1'
            . ' FROM group_table g1_'
            . ' INNER JOIN user_table u0_ ON (EXISTS ('
            . 'SELECT 1 FROM rel_user_to_group_table r2_'
            . ' INNER JOIN group_table g3_ ON r2_.user_group_id = g3_.id'
            . ' WHERE r2_.user_id = u0_.id AND g3_.id IN (g1_.id)'
            . '))'
            . ' WHERE u0_.id = 456 LIMIT 10)'
            . ') entity',
            [
                [
                    'entityId'        => '123',
                    'relatedEntityId' => '10',
                ],
                [
                    'entityId'        => '123',
                    'relatedEntityId' => '20',
                ],
            ]
        );

        $this->setQueryExpectationAt(
            $conn,
            2,
            'SELECT g0_.id AS id_0, g0_.name AS name_1, g0_.label AS label_2, g0_.public AS public_3'
            . ' FROM group_table g0_'
            . ' WHERE g0_.id IN (?, ?)',
            [
                [
                    'id_0'     => 10,
                    'name_1'   => 'group_name1',
                    'label_2'  => 'group_label1',
                    'public_3' => 0,
                ],
                [
                    'id_0'     => 20,
                    'name_1'   => 'group_name2',
                    'label_2'  => 'group_label2',
                    'public_3' => true,
                ],
            ],
            [1 => 10, 2 => 20],
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
                        'max_results' => 10,
                        'fields'      => [
                            'isException' => [
                                'exclude' => true
                            ]
                        ]
                    ],
                ],
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
                            'public' => false,
                        ],
                        [
                            'id'     => 20,
                            'name'   => 'group_name2',
                            'label'  => 'group_label2',
                            'public' => true,
                        ],
                    ],
                ],
                [
                    'id'     => 456,
                    'name'   => 'user_name2',
                    'groups' => [],
                ]
            ],
            $result
        );
    }

    public function testSubQueryLimitAndStringEntityId()
    {
        $qb = $this->em->getRepository('Test:Role')->createQueryBuilder('e')
            ->where('e.code IN (:ids)')
            ->setParameter('ids', ['id1', 'id2']);

        $conn = $this->getDriverConnectionMock($this->em);

        $this->setQueryExpectationAt(
            $conn,
            0,
            'SELECT r0_.code AS code_0, c1_.name AS name_1, r0_.category_name AS category_name_2'
            . ' FROM role_table r0_'
            . ' LEFT JOIN category_table c1_ ON r0_.category_name = c1_.name'
            . ' WHERE r0_.code IN (?, ?)',
            [
                [
                    'code_0'          => 'id1',
                    'name_1'          => 'category_1',
                    'category_name_2' => 'category_1',
                ],
                [
                    'code_0'          => 'id2',
                    'name_1'          => null,
                    'category_name_2' => null,
                ]
            ],
            [1 => 'id1', 2 => 'id2'],
            [1 => \PDO::PARAM_STR, 2 => \PDO::PARAM_STR]
        );

        $this->setQueryExpectationAt(
            $conn,
            1,
            'SELECT entity.code_0 AS entityId, entity.id_1 AS relatedEntityId'
            . ' FROM ('
            . '(SELECT r0_.code AS code_0, g1_.id AS id_1'
            . ' FROM group_table g1_'
            . ' INNER JOIN role_table r0_ ON (EXISTS ('
            . 'SELECT 1 FROM rel_role_to_group_table r2_'
            . ' INNER JOIN group_table g3_ ON r2_.role_group_id = g3_.id'
            . ' WHERE r2_.role_code = r0_.code AND g3_.id IN (g1_.id)'
            . '))'
            . ' WHERE r0_.code = \'id1\' LIMIT 10)'
            . ' UNION ALL'
            . ' (SELECT r0_.code AS code_0, g1_.id AS id_1'
            . ' FROM group_table g1_'
            . ' INNER JOIN role_table r0_ ON (EXISTS ('
            . 'SELECT 1 FROM rel_role_to_group_table r2_'
            . ' INNER JOIN group_table g3_ ON r2_.role_group_id = g3_.id'
            . ' WHERE r2_.role_code = r0_.code AND g3_.id IN (g1_.id)'
            . '))'
            . ' WHERE r0_.code = \'id2\' LIMIT 10)'
            . ') entity',
            [
                [
                    'entityId'        => 'id1',
                    'relatedEntityId' => 10,
                ],
                [
                    'entityId'        => 'id1',
                    'relatedEntityId' => 20,
                ],
            ]
        );

        $result = $this->serializer->serialize(
            $qb,
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'code'   => null,
                    'category' => [
                        'fields' => 'name'
                    ],
                    'groups' => [
                        'max_results' => 10,
                        'fields'      => 'id'
                    ],
                ],
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
                    'groups'   => null,
                ]
            ],
            $result
        );
    }

    /**
     * @deprecated since 1.9. Use 'exclude' attribute for a field instead of 'excluded_fields' for an entity
     */
    public function testManyToManyUnidirectionalDeprecated()
    {
        $qb = $this->em->getRepository('Test:User')->createQueryBuilder('e')
            ->where('e.id = :id')
            ->setParameter('id', 1);

        $conn = $this->getDriverConnectionMock($this->em);

        $this->setQueryExpectationAt(
            $conn,
            0,
            'SELECT u0_.id AS id_0, u0_.name AS name_1, u0_.category_name AS category_name_2'
            . ' FROM user_table u0_'
            . ' WHERE u0_.id = ?',
            [
                [
                    'id_0'            => 1,
                    'name_1'          => 'user_name',
                    'category_name_2' => 'category_name',
                ]
            ],
            [1 => 1],
            [1 => \PDO::PARAM_INT]
        );

        $this->setQueryExpectationAt(
            $conn,
            1,
            'SELECT u0_.id AS id_0,'
            . ' g1_.id AS id_1, g1_.name AS name_2, g1_.label AS label_3, g1_.public AS public_4'
            . ' FROM group_table g1_'
            . ' INNER JOIN user_table u0_ ON (EXISTS ('
            . 'SELECT 1 FROM rel_user_to_group_table r2_'
            . ' INNER JOIN group_table g3_ ON r2_.user_group_id = g3_.id'
            . ' WHERE r2_.user_id = u0_.id AND g3_.id IN (g1_.id)))'
            . ' WHERE u0_.id = ?',
            [
                [
                    'id_0'     => 1,
                    'id_1'     => 10,
                    'name_2'   => 'group_name1',
                    'label_3'  => 'group_label1',
                    'public_4' => 0,
                ],
                [
                    'id_0'     => 1,
                    'id_1'     => 20,
                    'name_2'   => 'group_name2',
                    'label_3'  => 'group_label2',
                    'public_4' => true,
                ],
            ],
            [1 => 1],
            [1 => \PDO::PARAM_INT]
        );

        $result = $this->serializer->serialize(
            $qb,
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'id'     => null,
                    'name'   => null,
                    'groups' => [
                        'excluded_fields' => ['isException']
                    ],
                ],
            ]
        );

        $this->assertArrayEquals(
            [
                [
                    'id'     => 1,
                    'name'   => 'user_name',
                    'groups' => [
                        [
                            'id'     => 10,
                            'name'   => 'group_name1',
                            'label'  => 'group_label1',
                            'public' => false,
                        ],
                        [
                            'id'     => 20,
                            'name'   => 'group_name2',
                            'label'  => 'group_label2',
                            'public' => true,
                        ],
                    ],
                ]
            ],
            $result
        );
    }

    public function testManyToManyUnidirectionalIdOnly()
    {
        $qb = $this->em->getRepository('Test:User')->createQueryBuilder('e')
            ->where('e.id = :id')
            ->setParameter('id', 1);

        $conn = $this->getDriverConnectionMock($this->em);

        $this->setQueryExpectationAt(
            $conn,
            0,
            'SELECT u0_.id AS id_0, u0_.name AS name_1, u0_.category_name AS category_name_2'
            . ' FROM user_table u0_'
            . ' WHERE u0_.id = ?',
            [
                [
                    'id_0'            => 1,
                    'name_1'          => 'user_name',
                    'category_name_2' => 'category_name',
                ]
            ],
            [1 => 1],
            [1 => \PDO::PARAM_INT]
        );
        $this->setQueryExpectationAt(
            $conn,
            1,
            'SELECT u0_.id AS id_0,'
            . ' g1_.id AS id_1'
            . ' FROM group_table g1_'
            . ' INNER JOIN user_table u0_ ON (EXISTS ('
            . 'SELECT 1 FROM rel_user_to_group_table r2_'
            . ' INNER JOIN group_table g3_ ON r2_.user_group_id = g3_.id'
            . ' WHERE r2_.user_id = u0_.id AND g3_.id IN (g1_.id)))'
            . ' WHERE u0_.id = ?',
            [
                [
                    'id_0' => 1,
                    'id_1' => 10,
                ],
                [
                    'id_0' => 1,
                    'id_1' => 20,
                ],
            ],
            [1 => 1],
            [1 => \PDO::PARAM_INT]
        );

        $result = $this->serializer->serialize(
            $qb,
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'id'     => null,
                    'name'   => null,
                    'groups' => [
                        'fields' => 'id'
                    ],
                ],
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

    /**
     * @deprecated since 1.9. Use 'order_by' attribute instead of 'orderBy'
     */
    public function testManyToManyBidirectionalIdOnlyAndDeprecatedOrderBy()
    {
        $qb = $this->em->getRepository('Test:User')->createQueryBuilder('e')
            ->where('e.id = :id')
            ->setParameter('id', 1);

        $conn = $this->getDriverConnectionMock($this->em);

        $this->setQueryExpectationAt(
            $conn,
            0,
            'SELECT u0_.id AS id_0, u0_.name AS name_1, u0_.category_name AS category_name_2'
            . ' FROM user_table u0_'
            . ' WHERE u0_.id = ?',
            [
                [
                    'id_0'            => 1,
                    'name_1'          => 'user_name',
                    'category_name_2' => 'category_name',
                ]
            ],
            [1 => 1],
            [1 => \PDO::PARAM_INT]
        );
        $this->setQueryExpectationAt(
            $conn,
            1,
            'SELECT u0_.id AS id_0,'
            . ' p1_.id AS id_1'
            . ' FROM product_table p1_'
            . ' INNER JOIN user_table u0_ ON (p1_.owner_id = u0_.id)'
            . ' WHERE u0_.id = ?'
            . ' ORDER BY p1_.id DESC',
            [
                [
                    'id_0' => 1,
                    'id_1' => 20,
                ],
                [
                    'id_0' => 1,
                    'id_1' => 10,
                ],
            ],
            [1 => 1],
            [1 => \PDO::PARAM_INT]
        );

        $result = $this->serializer->serialize(
            $qb,
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'id'       => null,
                    'name'     => null,
                    'products' => [
                        'fields'  => 'id',
                        'orderBy' => ['id' => 'DESC']
                    ],
                ],
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

    public function testManyToManyBidirectionalIdOnlyAndOrderBy()
    {
        $qb = $this->em->getRepository('Test:User')->createQueryBuilder('e')
            ->where('e.id = :id')
            ->setParameter('id', 1);

        $conn = $this->getDriverConnectionMock($this->em);

        $this->setQueryExpectationAt(
            $conn,
            0,
            'SELECT u0_.id AS id_0, u0_.name AS name_1, u0_.category_name AS category_name_2'
            . ' FROM user_table u0_'
            . ' WHERE u0_.id = ?',
            [
                [
                    'id_0'            => 1,
                    'name_1'          => 'user_name',
                    'category_name_2' => 'category_name',
                ]
            ],
            [1 => 1],
            [1 => \PDO::PARAM_INT]
        );
        $this->setQueryExpectationAt(
            $conn,
            1,
            'SELECT u0_.id AS id_0,'
            . ' p1_.id AS id_1'
            . ' FROM product_table p1_'
            . ' INNER JOIN user_table u0_ ON (p1_.owner_id = u0_.id)'
            . ' WHERE u0_.id = ?'
            . ' ORDER BY p1_.id DESC',
            [
                [
                    'id_0' => 1,
                    'id_1' => 20,
                ],
                [
                    'id_0' => 1,
                    'id_1' => 10,
                ],
            ],
            [1 => 1],
            [1 => \PDO::PARAM_INT]
        );

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
                    ],
                ],
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
    public function testManyToManyBidirectionalWithManyToOne()
    {
        $qb = $this->em->getRepository('Test:User')->createQueryBuilder('e')
            ->where('e.id = :id')
            ->setParameter('id', 1);

        $conn = $this->getDriverConnectionMock($this->em);

        $this->setQueryExpectationAt(
            $conn,
            0,
            'SELECT u0_.id AS id_0, u0_.name AS name_1, u0_.category_name AS category_name_2'
            . ' FROM user_table u0_'
            . ' WHERE u0_.id = ?',
            [
                [
                    'id_0'            => 1,
                    'name_1'          => 'user_name',
                    'category_name_2' => 'category_name',
                ]
            ],
            [1 => 1],
            [1 => \PDO::PARAM_INT]
        );
        $this->setQueryExpectationAt(
            $conn,
            1,
            'SELECT u0_.id AS id_0,'
            . ' p1_.id AS id_1'
            . ' FROM product_table p1_'
            . ' INNER JOIN user_table u0_ ON (p1_.owner_id = u0_.id)'
            . ' WHERE u0_.id = ?',
            [
                [
                    'id_0' => 1,
                    'id_1' => 10,
                ],
                [
                    'id_0' => 1,
                    'id_1' => 20,
                ],
            ],
            [1 => 1],
            [1 => \PDO::PARAM_INT]
        );
        $this->setQueryExpectationAt(
            $conn,
            2,
            'SELECT p0_.id AS id_0, p0_.name AS name_1,'
            . ' c1_.name AS name_2,'
            . ' p0_.category_name AS category_name_3, p0_.owner_id AS owner_id_4'
            . ' FROM product_table p0_'
            . ' LEFT JOIN category_table c1_ ON p0_.category_name = c1_.name'
            . ' WHERE p0_.id IN (?, ?)',
            [
                [
                    'id_0'            => 10,
                    'name_1'          => 'product_name1',
                    'name_2'          => 'category_name1',
                    'category_name_3' => 'category_name1',
                    'owner_id_4'      => 1,
                ],
                [
                    'id_0'            => 20,
                    'name_1'          => 'product_name2',
                    'name_2'          => 'category_name2',
                    'category_name_3' => 'category_name2',
                    'owner_id_4'      => 1,
                ],
            ],
            [1 => 10, 2 => 20],
            [1 => \PDO::PARAM_INT, 2 => \PDO::PARAM_INT]
        );

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
                                'fields' => 'name',
                            ]
                        ]
                    ],
                ],
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
                            'category' => 'category_name1',
                        ],
                        [
                            'id'       => 20,
                            'name'     => 'product_name2',
                            'category' => 'category_name2',
                        ],
                    ]
                ]
            ],
            $result
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testManyToManyBidirectionalWithManyToMany()
    {
        $qb = $this->em->getRepository('Test:User')->createQueryBuilder('e')
            ->where('e.id = :id')
            ->setParameter('id', 1);

        $conn = $this->getDriverConnectionMock($this->em);

        $this->setQueryExpectationAt(
            $conn,
            0,
            'SELECT u0_.id AS id_0, u0_.name AS name_1, u0_.category_name AS category_name_2'
            . ' FROM user_table u0_'
            . ' WHERE u0_.id = ?',
            [
                [
                    'id_0'            => 1,
                    'name_1'          => 'user_name',
                    'category_name_2' => 'category_name',
                ]
            ],
            [1 => 1],
            [1 => \PDO::PARAM_INT]
        );
        $this->setQueryExpectationAt(
            $conn,
            1,
            'SELECT u0_.id AS id_0,'
            . ' p1_.id AS id_1'
            . ' FROM product_table p1_'
            . ' INNER JOIN user_table u0_ ON (p1_.owner_id = u0_.id)'
            . ' WHERE u0_.id = ?',
            [
                [
                    'id_0' => 1,
                    'id_1' => 10,
                ],
                [
                    'id_0' => 1,
                    'id_1' => 20,
                ],
            ],
            [1 => 1],
            [1 => \PDO::PARAM_INT]
        );
        $this->setQueryExpectationAt(
            $conn,
            2,
            'SELECT p0_.id AS id_0, p0_.name AS name_1,'
            . ' p0_.category_name AS category_name_2, p0_.owner_id AS owner_id_3'
            . ' FROM product_table p0_'
            . ' WHERE p0_.id IN (?, ?)',
            [
                [
                    'id_0'            => 10,
                    'name_1'          => 'product_name1',
                    'name_2'          => 'category_name1',
                    'category_name_3' => 'category_name1',
                    'owner_id_4'      => 1,
                ],
                [
                    'id_0'            => 20,
                    'name_1'          => 'product_name2',
                    'name_2'          => 'category_name2',
                    'category_name_3' => 'category_name2',
                    'owner_id_4'      => 1,
                ],
            ],
            [1 => 10, 2 => 20],
            [1 => \PDO::PARAM_INT, 2 => \PDO::PARAM_INT]
        );

        $this->setQueryExpectationAt(
            $conn,
            3,
            'SELECT p0_.id AS id_0, g1_.id AS id_1'
            . ' FROM group_table g1_'
            . ' INNER JOIN product_table p0_ ON (EXISTS ('
            . 'SELECT 1 FROM rel_product_to_group_table r2_'
            . ' INNER JOIN group_table g3_ ON r2_.product_group_id = g3_.id'
            . ' WHERE r2_.product_id = p0_.id AND g3_.id IN (g1_.id)'
            . '))'
            . ' WHERE p0_.id IN (?, ?)',
            [
                [
                    'id_0' => 10,
                    'id_1' => 100,
                ],
                [
                    'id_0' => 20,
                    'id_1' => 200,
                ],
                [
                    'id_0' => 20,
                    'id_1' => 201,
                ],
            ],
            [1 => 10, 2 => 20],
            [1 => \PDO::PARAM_INT, 2 => \PDO::PARAM_INT]
        );

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
                                'fields' => 'id',
                            ]
                        ]
                    ],
                ],
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
                            'groups' => [100],
                        ],
                        [
                            'id'     => 20,
                            'name'   => 'product_name2',
                            'groups' => [200, 201],
                        ],
                    ]
                ]
            ],
            $result
        );
    }
}
