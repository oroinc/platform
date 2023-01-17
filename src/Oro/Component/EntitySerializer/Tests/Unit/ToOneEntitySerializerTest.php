<?php

namespace Oro\Component\EntitySerializer\Tests\Unit;

use Oro\Component\EntitySerializer\Tests\Unit\Fixtures\Entity\Product;

class ToOneEntitySerializerTest extends EntitySerializerTestCase
{
    public function testManyToOneUnidirectional(): void
    {
        $qb = $this->em->getRepository(Product::class)->createQueryBuilder('e')
            ->where('e.id = :id')
            ->setParameter('id', 1);

        $this->setQueryExpectation(
            $this->getDriverConnectionMock($this->em),
            'SELECT p0_.id AS id_0, p0_.name AS name_1,'
            . ' c1_.name AS name_2'
            . ' FROM product_table p0_'
            . ' LEFT JOIN category_table c1_ ON p0_.category_name = c1_.name'
            . ' WHERE p0_.id = ?',
            [
                [
                    'id_0'   => 1,
                    'name_1' => 'product_name',
                    'name_2' => 'category_name'
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
                ]
            ]
        );

        $this->assertArrayEquals(
            [
                [
                    'id'       => 1,
                    'name'     => 'product_name',
                    'category' => 'category_name'
                ]
            ],
            $result
        );
    }

    public function testManyToOneUnidirectionalNoIdentifierFieldInResult(): void
    {
        $qb = $this->em->getRepository(Product::class)->createQueryBuilder('e')
            ->where('e.id = :id')
            ->setParameter('id', 1);

        $this->setQueryExpectation(
            $this->getDriverConnectionMock($this->em),
            'SELECT p0_.id AS id_0, p0_.name AS name_1,'
            . ' c1_.name AS name_2, c1_.label AS label_3'
            . ' FROM product_table p0_'
            . ' LEFT JOIN category_table c1_ ON p0_.category_name = c1_.name'
            . ' WHERE p0_.id = ?',
            [
                [
                    'id_0'    => 1,
                    'name_1'  => 'product_name',
                    'name_2'  => 'category_name',
                    'label_3' => 'category_label'
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
                ]
            ]
        );

        $this->assertArrayEquals(
            [
                [
                    'id'       => 1,
                    'name'     => 'product_name',
                    'category' => 'category_label'
                ]
            ],
            $result
        );
    }

    public function testManyToOneBidirectional(): void
    {
        $qb = $this->em->getRepository(Product::class)->createQueryBuilder('e')
            ->where('e.id = :id')
            ->setParameter('id', 1);

        $this->setQueryExpectation(
            $this->getDriverConnectionMock($this->em),
            'SELECT p0_.id AS id_0, p0_.name AS name_1,'
            . ' u1_.id AS id_2, u1_.name AS name_3'
            . ' FROM product_table p0_'
            . ' LEFT JOIN user_table u1_ ON p0_.owner_id = u1_.id'
            . ' WHERE p0_.id = ?',
            [
                [
                    'id_0'   => 1,
                    'name_1' => 'product_name',
                    'id_2'   => 10,
                    'name_3' => 'user_name'
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
                ]
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
                    ]
                ]
            ],
            $result
        );
    }

    public function testManyToOneBidirectionalIdOnly(): void
    {
        $qb = $this->em->getRepository(Product::class)->createQueryBuilder('e')
            ->where('e.id = :id')
            ->setParameter('id', 1);

        $this->setQueryExpectation(
            $this->getDriverConnectionMock($this->em),
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
                ]
            ]
        );

        $this->assertArrayEquals(
            [
                [
                    'id'    => 1,
                    'name'  => 'product_name',
                    'owner' => 10
                ]
            ],
            $result
        );
    }

    public function testManyToOneBidirectionalWithManyToManyIds(): void
    {
        $qb = $this->em->getRepository(Product::class)->createQueryBuilder('e')
            ->where('e.id = :id')
            ->setParameter('id', 1);

        $this->addQueryExpectation(
            'SELECT p0_.id AS id_0, p0_.name AS name_1,'
            . ' u1_.id AS id_2, u1_.name AS name_3'
            . ' FROM product_table p0_'
            . ' LEFT JOIN user_table u1_ ON p0_.owner_id = u1_.id'
            . ' WHERE p0_.id = ?',
            [
                [
                    'id_0'   => 1,
                    'name_1' => 'product_name',
                    'id_2'   => 10,
                    'name_3' => 'user_name'
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
                    'id_0' => 10,
                    'id_1' => 100
                ],
                [
                    'id_0' => 10,
                    'id_1' => 101
                ]
            ],
            [1 => 10],
            [1 => \PDO::PARAM_INT]
        );
        $this->applyQueryExpectations($this->getDriverConnectionMock($this->em));

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
                    'id'    => 1,
                    'name'  => 'product_name',
                    'owner' => [
                        'id'     => 10,
                        'name'   => 'user_name',
                        'groups' => [100, 101]
                    ]
                ]
            ],
            $result
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testManyToOneBidirectionalWithManyToMany(): void
    {
        $qb = $this->em->getRepository(Product::class)->createQueryBuilder('e')
            ->where('e.id = :id')
            ->setParameter('id', 1);

        $this->addQueryExpectation(
            'SELECT p0_.id AS id_0, p0_.name AS name_1,'
            . ' u1_.id AS id_2, u1_.name AS name_3'
            . ' FROM product_table p0_'
            . ' LEFT JOIN user_table u1_ ON p0_.owner_id = u1_.id'
            . ' WHERE p0_.id = ?',
            [
                [
                    'id_0'   => 1,
                    'name_1' => 'product_name',
                    'id_2'   => 10,
                    'name_3' => 'user_name'
                ]
            ],
            [1 => 1],
            [1 => \PDO::PARAM_INT]
        );
        $this->addQueryExpectation(
            'SELECT u0_.id AS id_0, g1_.id AS id_1, g1_.name AS name_2'
            . ' FROM user_table u0_'
            . ' INNER JOIN rel_user_to_group_table r2_ ON u0_.id = r2_.user_id'
            . ' INNER JOIN group_table g1_ ON g1_.id = r2_.user_group_id'
            . ' WHERE u0_.id = ?',
            [
                [
                    'id_0'   => 10,
                    'id_1'   => 100,
                    'name_2' => 'owner_group_name1'
                ],
                [
                    'id_0'   => 10,
                    'id_1'   => 101,
                    'name_2' => 'owner_group_name2'
                ]
            ],
            [1 => 10],
            [1 => \PDO::PARAM_INT]
        );
        $this->applyQueryExpectations($this->getDriverConnectionMock($this->em));

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
                                    'name' => null
                                ]
                            ]
                        ]
                    ]
                ]
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
                            ['id' => 101, 'name' => 'owner_group_name2']
                        ]
                    ]
                ]
            ],
            $result
        );
    }

    public function testManyToOneWithRenamedIdentifierField(): void
    {
        $qb = $this->em->getRepository(Product::class)->createQueryBuilder('e')
            ->where('e.id = :id')
            ->setParameter('id', 1);

        $this->setQueryExpectation(
            $this->getDriverConnectionMock($this->em),
            'SELECT p0_.id AS id_0, p0_.name AS name_1,'
            . ' c1_.name AS name_2'
            . ' FROM product_table p0_'
            . ' LEFT JOIN category_table c1_ ON p0_.category_name = c1_.name'
            . ' WHERE p0_.id = ?',
            [
                [
                    'id_0'   => 1,
                    'name_1' => 'product_name',
                    'name_2' => 'category_name'
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
                ]
            ]
        );

        $this->assertArrayEquals(
            [
                [
                    'id'       => 1,
                    'name'     => 'product_name',
                    'category' => [
                        'id' => 'category_name'
                    ]
                ]
            ],
            $result
        );
    }

    public function testManyToOneCollapsedWithRenamedIdentifierField(): void
    {
        $qb = $this->em->getRepository(Product::class)->createQueryBuilder('e')
            ->where('e.id = :id')
            ->setParameter('id', 1);

        $this->setQueryExpectation(
            $this->getDriverConnectionMock($this->em),
            'SELECT p0_.id AS id_0, p0_.name AS name_1,'
            . ' c1_.name AS name_2'
            . ' FROM product_table p0_'
            . ' LEFT JOIN category_table c1_ ON p0_.category_name = c1_.name'
            . ' WHERE p0_.id = ?',
            [
                [
                    'id_0'   => 1,
                    'name_1' => 'product_name',
                    'name_2' => 'category_name'
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
                ]
            ]
        );

        $this->assertArrayEquals(
            [
                [
                    'id'       => 1,
                    'name'     => 'product_name',
                    'category' => 'category_name'
                ]
            ],
            $result
        );
    }
}
