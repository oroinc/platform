<?php

namespace Oro\Bundle\SoapBundle\Tests\Unit\Serializer;

class ToOneEntitySerializerTest extends EntitySerializerTestCase
{
    public function testManyToOneUnidirectional()
    {
        $qb = $this->em->getRepository('Test:Product')->createQueryBuilder('e')
            ->where('e.id = :id')
            ->setParameter('id', 1);

        $this->setQueryExpectation(
            $this->getDriverConnectionMock($this->em),
            'SELECT o0_.id AS id0, o0_.name AS name1,'
            . ' o1_.name AS name2,'
            . ' o0_.category_name AS category_name3, o0_.owner_id AS owner_id4'
            . ' FROM oro_test_serializer_product o0_'
            . ' LEFT JOIN oro_test_serializer_category o1_ ON o0_.category_name = o1_.name'
            . ' WHERE o0_.id = ?',
            [
                [
                    'id0'            => 1,
                    'name1'          => 'product_name',
                    'name2'          => 'category_name',
                    'category_name3' => 'category_name',
                    'owner_id4'      => 10,
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
            'SELECT o0_.id AS id0, o0_.name AS name1,'
            . ' o1_.name AS name2, o1_.label AS label3,'
            . ' o0_.category_name AS category_name4, o0_.owner_id AS owner_id5'
            . ' FROM oro_test_serializer_product o0_'
            . ' LEFT JOIN oro_test_serializer_category o1_ ON o0_.category_name = o1_.name'
            . ' WHERE o0_.id = ?',
            [
                [
                    'id0'            => 1,
                    'name1'          => 'product_name',
                    'name2'          => 'category_name',
                    'label3'         => 'category_label',
                    'category_name4' => 'category_name',
                    'owner_id5'      => 10,
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
            'SELECT o0_.id AS id0, o0_.name AS name1,'
            . ' o1_.id AS id2, o1_.name AS name3,'
            . ' o0_.category_name AS category_name4, o0_.owner_id AS owner_id5,'
            . ' o1_.category_name AS category_name6'
            . ' FROM oro_test_serializer_product o0_'
            . ' LEFT JOIN oro_test_serializer_user o1_ ON o0_.owner_id = o1_.id'
            . ' WHERE o0_.id = ?',
            [
                [
                    'id0'            => 1,
                    'name1'          => 'product_name',
                    'id2'            => 10,
                    'name3'          => 'user_name',
                    'category_name4' => 'category_name',
                    'owner_id5'      => 10,
                    'category_name6' => 'user_category_name',
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
            'SELECT o0_.id AS id0, o0_.name AS name1,'
            . ' o1_.id AS id2,'
            . ' o0_.category_name AS category_name3, o0_.owner_id AS owner_id4,'
            . ' o1_.category_name AS category_name5'
            . ' FROM oro_test_serializer_product o0_'
            . ' LEFT JOIN oro_test_serializer_user o1_ ON o0_.owner_id = o1_.id'
            . ' WHERE o0_.id = ?',
            [
                [
                    'id0'            => 1,
                    'name1'          => 'product_name',
                    'id2'            => 10,
                    'category_name3' => 'category_name',
                    'owner_id4'      => 10,
                    'category_name5' => 'user_category_name',
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
            'SELECT o0_.id AS id0, o0_.name AS name1,'
            . ' o1_.name AS name2, o1_.label AS label3,'
            . ' o2_.id AS id4, o2_.name AS name5,'
            . ' o0_.category_name AS category_name6, o0_.owner_id AS owner_id7,'
            . ' o2_.category_name AS category_name8'
            . ' FROM oro_test_serializer_product o0_'
            . ' LEFT JOIN oro_test_serializer_category o1_ ON o0_.category_name = o1_.name'
            . ' LEFT JOIN oro_test_serializer_user o2_ ON o0_.owner_id = o2_.id'
            . ' WHERE o0_.id = ?',
            [
                [
                    'id0'            => 1,
                    'name1'          => 'product_name',
                    'name2'          => 'product_category_name',
                    'label3'         => 'product_category_label',
                    'id4'            => 10,
                    'name5'          => 'user_name',
                    'category_name6' => 'product_category_name',
                    'owner_id7'      => 10,
                    'category_name8' => 'user_category_name',
                ]
            ],
            [1 => 1],
            [1 => \PDO::PARAM_INT]
        );
        $this->setQueryExpectationAt(
            $conn,
            1,
            'SELECT t0.name AS name1, t0.label AS label2'
            . ' FROM oro_test_serializer_category t0'
            . ' WHERE t0.name = ?',
            [
                [
                    'name1'  => 'user_category_name',
                    'label2' => 'user_category_label',
                ]
            ],
            [1 => 'user_category_name'],
            [1 => \PDO::PARAM_STR]
        );

        $result = $this->serializer->serialize(
            $qb,
            [
                'fields' => [
                    'owner' => [
                        'fields' => [
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
                    'category' => 'product_category_name',
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
}
