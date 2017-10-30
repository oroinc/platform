<?php

namespace Oro\Component\EntitySerializer\Tests\Unit;

class ToOneEntitySerializerTest extends EntitySerializerTestCase
{
    public function testManyToOneUnidirectional()
    {
        $qb = $this->em->getRepository('Test:Product')->createQueryBuilder('e')
            ->where('e.id = :id')
            ->setParameter('id', 1);

        $this->setQueryExpectation(
            $this->getDriverConnectionMock($this->em),
            'SELECT p0_.id AS id_0, p0_.name AS name_1,'
            . ' c1_.name AS name_2,'
            . ' p0_.category_name AS category_name_3, p0_.owner_id AS owner_id_4'
            . ' FROM product_table p0_'
            . ' LEFT JOIN category_table c1_ ON p0_.category_name = c1_.name'
            . ' WHERE p0_.id = ?',
            [
                [
                    'id_0'            => 1,
                    'name_1'          => 'product_name',
                    'name_2'          => 'category_name',
                    'category_name_3' => 'category_name',
                    'owner_id_4'      => 10,
                ]
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
                    'category' => [
                        'fields' => 'name'
                    ]
                ],
            ]
        );

        $this->assertArrayEquals(
            [
                [
                    'id'       => 1,
                    'name'     => 'product_name',
                    'category' => 'category_name',
                ]
            ],
            $result
        );
    }

    public function testManyToOneUnidirectionalNoIdentifierFieldInResult()
    {
        $qb = $this->em->getRepository('Test:Product')->createQueryBuilder('e')
            ->where('e.id = :id')
            ->setParameter('id', 1);

        $this->setQueryExpectation(
            $this->getDriverConnectionMock($this->em),
            'SELECT p0_.id AS id_0, p0_.name AS name_1,'
            . ' c1_.name AS name_2, c1_.label AS label_3,'
            . ' p0_.category_name AS category_name_4, p0_.owner_id AS owner_id_5'
            . ' FROM product_table p0_'
            . ' LEFT JOIN category_table c1_ ON p0_.category_name = c1_.name'
            . ' WHERE p0_.id = ?',
            [
                [
                    'id_0'            => 1,
                    'name_1'          => 'product_name',
                    'name_2'          => 'category_name',
                    'label_3'         => 'category_label',
                    'category_name_4' => 'category_name',
                    'owner_id_5'      => 10,
                ]
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
                    'category' => [
                        'fields' => 'label'
                    ]
                ],
            ]
        );

        $this->assertArrayEquals(
            [
                [
                    'id'       => 1,
                    'name'     => 'product_name',
                    'category' => 'category_label',
                ]
            ],
            $result
        );
    }

    public function testManyToOneBidirectional()
    {
        $qb = $this->em->getRepository('Test:Product')->createQueryBuilder('e')
            ->where('e.id = :id')
            ->setParameter('id', 1);

        $this->setQueryExpectation(
            $this->getDriverConnectionMock($this->em),
            'SELECT p0_.id AS id_0, p0_.name AS name_1,'
            . ' u1_.id AS id_2, u1_.name AS name_3,'
            . ' p0_.category_name AS category_name_4, p0_.owner_id AS owner_id_5,'
            . ' u1_.category_name AS category_name_6'
            . ' FROM product_table p0_'
            . ' LEFT JOIN user_table u1_ ON p0_.owner_id = u1_.id'
            . ' WHERE p0_.id = ?',
            [
                [
                    'id_0'            => 1,
                    'name_1'          => 'product_name',
                    'id_2'            => 10,
                    'name_3'          => 'user_name',
                    'category_name_4' => 'category_name',
                    'owner_id_5'      => 10,
                    'category_name_6' => 'user_category_name',
                ]
            ],
            [1 => 1],
            [1 => \PDO::PARAM_INT]
        );

        $result = $this->serializer->serialize(
            $qb,
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'id'    => null,
                    'name'  => null,
                    'owner' => [
                        'exclusion_policy' => 'all',
                        'fields'           => [
                            'id'   => null,
                            'name' => null
                        ]
                    ]
                ],
            ]
        );

        $this->assertArrayEquals(
            [
                [
                    'id'    => 1,
                    'name'  => 'product_name',
                    'owner' => [
                        'id'   => 10,
                        'name' => 'user_name'
                    ],
                ]
            ],
            $result
        );
    }

    public function testManyToOneBidirectionalIdOnly()
    {
        $qb = $this->em->getRepository('Test:Product')->createQueryBuilder('e')
            ->where('e.id = :id')
            ->setParameter('id', 1);

        $this->setQueryExpectation(
            $this->getDriverConnectionMock($this->em),
            'SELECT p0_.id AS id_0, p0_.name AS name_1,'
            . ' u1_.id AS id_2,'
            . ' p0_.category_name AS category_name_3, p0_.owner_id AS owner_id_4,'
            . ' u1_.category_name AS category_name_5'
            . ' FROM product_table p0_'
            . ' LEFT JOIN user_table u1_ ON p0_.owner_id = u1_.id'
            . ' WHERE p0_.id = ?',
            [
                [
                    'id_0'            => 1,
                    'name_1'          => 'product_name',
                    'id_2'            => 10,
                    'category_name_3' => 'category_name',
                    'owner_id_4'      => 10,
                    'category_name_5' => 'user_category_name',
                ]
            ],
            [1 => 1],
            [1 => \PDO::PARAM_INT]
        );

        $result = $this->serializer->serialize(
            $qb,
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'id'    => null,
                    'name'  => null,
                    'owner' => [
                        'fields' => 'id'
                    ]
                ],
            ]
        );

        $this->assertArrayEquals(
            [
                [
                    'id'    => 1,
                    'name'  => 'product_name',
                    'owner' => 10,
                ]
            ],
            $result
        );
    }

    /**
     * tests that product category is serialized by __toString() method of Category entity,
     * but user category is serialized by the given configs
     */
    public function testManyToOneWithConfigForThirdLevelRelation()
    {
        $qb = $this->em->getRepository('Test:Product')->createQueryBuilder('e')
            ->where('e.id = :id')
            ->setParameter('id', 1);

        $conn = $this->getDriverConnectionMock($this->em);

        $this->setQueryExpectationAt(
            $conn,
            0,
            'SELECT p0_.id AS id_0, p0_.name AS name_1,'
            . ' c1_.name AS name_2, c1_.label AS label_3,'
            . ' u2_.id AS id_4, u2_.name AS name_5,'
            . ' p0_.category_name AS category_name_6, p0_.owner_id AS owner_id_7,'
            . ' u2_.category_name AS category_name_8'
            . ' FROM product_table p0_'
            . ' LEFT JOIN category_table c1_ ON p0_.category_name = c1_.name'
            . ' LEFT JOIN user_table u2_ ON p0_.owner_id = u2_.id'
            . ' WHERE p0_.id = ?',
            [
                [
                    'id_0'            => 1,
                    'name_1'          => 'product_name',
                    'name_2'          => 'product_category_name',
                    'label_3'         => 'product_category_label',
                    'id_4'            => 10,
                    'name_5'          => 'user_name',
                    'category_name_6' => 'product_category_name',
                    'owner_id_7'      => 10,
                    'category_name_8' => 'user_category_name',
                ]
            ],
            [1 => 1],
            [1 => \PDO::PARAM_INT]
        );
        $this->setQueryExpectationAt(
            $conn,
            1,
            'SELECT t0.name AS name_1, t0.label AS label_2'
            . ' FROM category_table t0'
            . ' WHERE t0.name = ?',
            [
                [
                    'name_1'  => 'user_category_name',
                    'label_2' => 'user_category_label',
                ]
            ],
            [1 => 'user_category_name'],
            [1 => \PDO::PARAM_STR]
        );

        $result = $this->serializer->serialize(
            $qb,
            [
                'fields' => [
                    'category' => null,
                    'groups'   => ['exclude' => true],
                    'owner'    => [
                        'exclusion_policy' => 'all',
                        'fields'           => [
                            'id'       => null,
                            'name'     => null,
                            'category' => ['fields' => 'label']
                        ]
                    ]
                ],
            ]
        );

        $this->assertArrayEquals(
            [
                [
                    'id'       => 1,
                    'name'     => 'product_name',
                    'category' => [
                        'name'  => 'product_category_name',
                        'label' => 'product_category_label'
                    ],
                    'owner'    => [
                        'id'       => 10,
                        'name'     => 'user_name',
                        'category' => 'user_category_label'
                    ],
                ]
            ],
            $result
        );
    }

    public function testManyToOneBidirectionalWithManyToManyIds()
    {
        $qb = $this->em->getRepository('Test:Product')->createQueryBuilder('e')
            ->where('e.id = :id')
            ->setParameter('id', 1);

        $conn = $this->getDriverConnectionMock($this->em);

        $this->setQueryExpectationAt(
            $conn,
            0,
            'SELECT p0_.id AS id_0, p0_.name AS name_1,'
            . ' u1_.id AS id_2, u1_.name AS name_3,'
            . ' p0_.category_name AS category_name_4, p0_.owner_id AS owner_id_5,'
            . ' u1_.category_name AS category_name_6'
            . ' FROM product_table p0_'
            . ' LEFT JOIN user_table u1_ ON p0_.owner_id = u1_.id'
            . ' WHERE p0_.id = ?',
            [
                [
                    'id_0'            => 1,
                    'name_1'          => 'product_name',
                    'id_2'            => 10,
                    'name_3'          => 'user_name',
                    'category_name_4' => 'category_name',
                    'owner_id_5'      => 10,
                    'category_name_6' => 'user_category_name',
                ]
            ],
            [1 => 1],
            [1 => \PDO::PARAM_INT]
        );
        $this->setQueryExpectationAt(
            $conn,
            1,
            'SELECT u0_.id AS id_0, g1_.id AS id_1'
            . ' FROM group_table g1_'
            . ' INNER JOIN user_table u0_ ON (EXISTS ('
            . 'SELECT 1'
            . ' FROM rel_user_to_group_table r2_'
            . ' INNER JOIN group_table g3_ ON r2_.user_group_id = g3_.id'
            . ' WHERE r2_.user_id = u0_.id AND g3_.id IN (g1_.id)'
            . '))'
            . ' WHERE u0_.id = ?',
            [
                [
                    'id_0' => 10,
                    'id_1' => 100,
                ],
                [
                    'id_0' => 10,
                    'id_1' => 101,
                ],
            ],
            [1 => 10],
            [1 => \PDO::PARAM_INT]
        );

        $result = $this->serializer->serialize(
            $qb,
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'id'    => null,
                    'name'  => null,
                    'owner' => [
                        'exclusion_policy' => 'all',
                        'fields'           => [
                            'id'     => null,
                            'name'   => null,
                            'groups' => [
                                'fields' => 'id',
                            ]
                        ]
                    ]
                ],
            ]
        );

        $this->assertArrayEquals(
            [
                [
                    'id'    => 1,
                    'name'  => 'product_name',
                    'owner' => [
                        'id'     => 10,
                        'name'   => 'user_name',
                        'groups' => [100, 101],
                    ],
                ]
            ],
            $result
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testManyToOneBidirectionalWithManyToMany()
    {
        $qb = $this->em->getRepository('Test:Product')->createQueryBuilder('e')
            ->where('e.id = :id')
            ->setParameter('id', 1);

        $conn = $this->getDriverConnectionMock($this->em);

        $this->setQueryExpectationAt(
            $conn,
            0,
            'SELECT p0_.id AS id_0, p0_.name AS name_1,'
            . ' u1_.id AS id_2, u1_.name AS name_3,'
            . ' p0_.category_name AS category_name_4, p0_.owner_id AS owner_id_5,'
            . ' u1_.category_name AS category_name_6'
            . ' FROM product_table p0_'
            . ' LEFT JOIN user_table u1_ ON p0_.owner_id = u1_.id'
            . ' WHERE p0_.id = ?',
            [
                [
                    'id_0'            => 1,
                    'name_1'          => 'product_name',
                    'id_2'            => 10,
                    'name_3'          => 'user_name',
                    'category_name_4' => 'category_name',
                    'owner_id_5'      => 10,
                    'category_name_6' => 'user_category_name',
                ]
            ],
            [1 => 1],
            [1 => \PDO::PARAM_INT]
        );
        $this->setQueryExpectationAt(
            $conn,
            1,
            'SELECT u0_.id AS id_0, g1_.id AS id_1, g1_.name AS name_2'
            . ' FROM group_table g1_'
            . ' INNER JOIN user_table u0_ ON (EXISTS ('
            . 'SELECT 1'
            . ' FROM rel_user_to_group_table r2_'
            . ' INNER JOIN group_table g3_ ON r2_.user_group_id = g3_.id'
            . ' WHERE r2_.user_id = u0_.id AND g3_.id IN (g1_.id)'
            . '))'
            . ' WHERE u0_.id = ?',
            [
                [
                    'id_0'   => 10,
                    'id_1'   => 100,
                    'name_2' => 'owner_group_name1',
                ],
                [
                    'id_0'   => 10,
                    'id_1'   => 101,
                    'name_2' => 'owner_group_name2',
                ],
            ],
            [1 => 10],
            [1 => \PDO::PARAM_INT]
        );

        $result = $this->serializer->serialize(
            $qb,
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'id'    => null,
                    'name'  => null,
                    'owner' => [
                        'exclusion_policy' => 'all',
                        'fields'           => [
                            'id'     => null,
                            'name'   => null,
                            'groups' => [
                                'exclusion_policy' => 'all',
                                'fields'           => [
                                    'id'   => null,
                                    'name' => null,
                                ]
                            ]
                        ]
                    ]
                ],
            ]
        );

        $this->assertArrayEquals(
            [
                [
                    'id'    => 1,
                    'name'  => 'product_name',
                    'owner' => [
                        'id'     => 10,
                        'name'   => 'user_name',
                        'groups' => [
                            ['id' => 100, 'name' => 'owner_group_name1'],
                            ['id' => 101, 'name' => 'owner_group_name2'],
                        ],
                    ],
                ]
            ],
            $result
        );
    }

    public function testManyToOneWithRenamedIdentifierField()
    {
        $qb = $this->em->getRepository('Test:Product')->createQueryBuilder('e')
            ->where('e.id = :id')
            ->setParameter('id', 1);

        $this->setQueryExpectation(
            $this->getDriverConnectionMock($this->em),
            'SELECT p0_.id AS id_0, p0_.name AS name_1,'
            . ' c1_.name AS name_2,'
            . ' p0_.category_name AS category_name_3, p0_.owner_id AS owner_id_4'
            . ' FROM product_table p0_'
            . ' LEFT JOIN category_table c1_ ON p0_.category_name = c1_.name'
            . ' WHERE p0_.id = ?',
            [
                [
                    'id_0'            => 1,
                    'name_1'          => 'product_name',
                    'name_2'          => 'category_name',
                    'category_name_3' => 'category_name',
                    'owner_id_4'      => 10,
                ]
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
                    'category' => [
                        'exclusion_policy' => 'all',
                        'fields'           => [
                            'id' => [
                                'property_path' => 'name'
                            ]
                        ]
                    ]
                ],
            ]
        );

        $this->assertArrayEquals(
            [
                [
                    'id'       => 1,
                    'name'     => 'product_name',
                    'category' => [
                        'id' => 'category_name'
                    ],
                ]
            ],
            $result
        );
    }

    public function testManyToOneCollapsedWithRenamedIdentifierField()
    {
        $qb = $this->em->getRepository('Test:Product')->createQueryBuilder('e')
            ->where('e.id = :id')
            ->setParameter('id', 1);

        $this->setQueryExpectation(
            $this->getDriverConnectionMock($this->em),
            'SELECT p0_.id AS id_0, p0_.name AS name_1,'
            . ' c1_.name AS name_2,'
            . ' p0_.category_name AS category_name_3, p0_.owner_id AS owner_id_4'
            . ' FROM product_table p0_'
            . ' LEFT JOIN category_table c1_ ON p0_.category_name = c1_.name'
            . ' WHERE p0_.id = ?',
            [
                [
                    'id_0'            => 1,
                    'name_1'          => 'product_name',
                    'name_2'          => 'category_name',
                    'category_name_3' => 'category_name',
                    'owner_id_4'      => 10,
                ]
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
                    'category' => [
                        'exclusion_policy' => 'all',
                        'collapse'         => true,
                        'fields'           => [
                            'id' => [
                                'property_path' => 'name'
                            ]
                        ]
                    ]
                ],
            ]
        );

        $this->assertArrayEquals(
            [
                [
                    'id'       => 1,
                    'name'     => 'product_name',
                    'category' => 'category_name',
                ]
            ],
            $result
        );
    }
}
