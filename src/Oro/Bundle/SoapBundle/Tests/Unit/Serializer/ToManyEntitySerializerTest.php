<?php

namespace Oro\Bundle\SoapBundle\Tests\Unit\Serializer;

class ToManyEntitySerializerTest extends EntitySerializerTestCase
{
    public function testManyToManyUnidirectional()
    {
        $qb = $this->em->getRepository('Test:User')->createQueryBuilder('e')
            ->where('e.id = :id')
            ->setParameter('id', 1);

        $conn = $this->getDriverConnectionMock($this->em);

        $this->setQueryExpectationAt(
            $conn,
            0,
            'SELECT o0_.id AS id0, o0_.name AS name1, o0_.category_name AS category_name2'
            . ' FROM oro_test_serializer_user o0_'
            . ' WHERE o0_.id = ?',
            [
                [
                    'id0'            => 1,
                    'name1'          => 'user_name',
                    'category_name2' => 'category_name',
                ]
            ],
            [1 => 1],
            [1 => \PDO::PARAM_INT]
        );
        $this->setQueryExpectationAt(
            $conn,
            1,
            'SELECT o0_.id AS id0,'
            . ' o1_.id AS id1, o1_.name AS name2, o1_.label AS label3, o1_.public AS public4'
            . ' FROM oro_test_serializer_group o1_'
            . ' INNER JOIN oro_test_serializer_user o0_ ON (EXISTS ('
            . 'SELECT 1 FROM oro_test_serializer_user_to_group o2_'
            . ' INNER JOIN oro_test_serializer_group o3_ ON o2_.user_group_id = o3_.id'
            . ' WHERE o2_.user_id = o0_.id AND o3_.id = o1_.id))'
            . ' WHERE o0_.id IN (?)',
            [
                [
                    'id0'     => 1,
                    'id1'     => 10,
                    'name2'   => 'group_name1',
                    'label3'  => 'group_label1',
                    'public4' => 0,
                ],
                [
                    'id0'     => 1,
                    'id1'     => 20,
                    'name2'   => 'group_name2',
                    'label3'  => 'group_label2',
                    'public4' => true,
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
                    'groups' => null,
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
            'SELECT o0_.id AS id0, o0_.name AS name1, o0_.category_name AS category_name2'
            . ' FROM oro_test_serializer_user o0_'
            . ' WHERE o0_.id = ?',
            [
                [
                    'id0'            => 1,
                    'name1'          => 'user_name',
                    'category_name2' => 'category_name',
                ]
            ],
            [1 => 1],
            [1 => \PDO::PARAM_INT]
        );
        $this->setQueryExpectationAt(
            $conn,
            1,
            'SELECT o0_.id AS id0,'
            . ' o1_.id AS id1'
            . ' FROM oro_test_serializer_group o1_'
            . ' INNER JOIN oro_test_serializer_user o0_ ON (EXISTS ('
            . 'SELECT 1 FROM oro_test_serializer_user_to_group o2_'
            . ' INNER JOIN oro_test_serializer_group o3_ ON o2_.user_group_id = o3_.id'
            . ' WHERE o2_.user_id = o0_.id AND o3_.id = o1_.id))'
            . ' WHERE o0_.id IN (?)',
            [
                [
                    'id0' => 1,
                    'id1' => 10,
                ],
                [
                    'id0' => 1,
                    'id1' => 20,
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

    public function testManyToManyBidirectionalIdOnlyAndOrderBy()
    {
        $qb = $this->em->getRepository('Test:User')->createQueryBuilder('e')
            ->where('e.id = :id')
            ->setParameter('id', 1);

        $conn = $this->getDriverConnectionMock($this->em);

        $this->setQueryExpectationAt(
            $conn,
            0,
            'SELECT o0_.id AS id0, o0_.name AS name1, o0_.category_name AS category_name2'
            . ' FROM oro_test_serializer_user o0_'
            . ' WHERE o0_.id = ?',
            [
                [
                    'id0'            => 1,
                    'name1'          => 'user_name',
                    'category_name2' => 'category_name',
                ]
            ],
            [1 => 1],
            [1 => \PDO::PARAM_INT]
        );
        $this->setQueryExpectationAt(
            $conn,
            1,
            'SELECT o0_.id AS id0,'
            . ' o1_.id AS id1'
            . ' FROM oro_test_serializer_product o1_'
            . ' INNER JOIN oro_test_serializer_user o0_ ON (o1_.owner_id = o0_.id)'
            . ' WHERE o0_.id IN (?)'
            . ' ORDER BY o1_.id DESC',
            [
                [
                    'id0' => 1,
                    'id1' => 20,
                ],
                [
                    'id0' => 1,
                    'id1' => 10,
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
            'SELECT o0_.id AS id0, o0_.name AS name1, o0_.category_name AS category_name2'
            . ' FROM oro_test_serializer_user o0_'
            . ' WHERE o0_.id = ?',
            [
                [
                    'id0'            => 1,
                    'name1'          => 'user_name',
                    'category_name2' => 'category_name',
                ]
            ],
            [1 => 1],
            [1 => \PDO::PARAM_INT]
        );
        $this->setQueryExpectationAt(
            $conn,
            1,
            'SELECT o0_.id AS id0,'
            . ' o1_.id AS id1'
            . ' FROM oro_test_serializer_product o1_'
            . ' INNER JOIN oro_test_serializer_user o0_ ON (o1_.owner_id = o0_.id)'
            . ' WHERE o0_.id IN (?)',
            [
                [
                    'id0' => 1,
                    'id1' => 10,
                ],
                [
                    'id0' => 1,
                    'id1' => 20,
                ],
            ],
            [1 => 1],
            [1 => \PDO::PARAM_INT]
        );
        $this->setQueryExpectationAt(
            $conn,
            2,
            'SELECT o0_.id AS id0, o0_.name AS name1,'
            . ' o1_.name AS name2,'
            . ' o0_.category_name AS category_name3, o0_.owner_id AS owner_id4'
            . ' FROM oro_test_serializer_product o0_'
            . ' LEFT JOIN oro_test_serializer_category o1_ ON o0_.category_name = o1_.name'
            . ' WHERE o0_.id IN (?, ?)',
            [
                [
                    'id0'            => 10,
                    'name1'          => 'product_name1',
                    'name2'          => 'category_name1',
                    'category_name3' => 'category_name1',
                    'owner_id4'      => 1,
                ],
                [
                    'id0'            => 20,
                    'name1'          => 'product_name2',
                    'name2'          => 'category_name2',
                    'category_name3' => 'category_name2',
                    'owner_id4'      => 1,
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
}
