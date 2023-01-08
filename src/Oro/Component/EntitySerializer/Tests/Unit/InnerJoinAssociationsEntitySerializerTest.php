<?php

namespace Oro\Component\EntitySerializer\Tests\Unit;

use Oro\Component\EntitySerializer\Tests\Unit\Fixtures\Entity\Product;

class InnerJoinAssociationsEntitySerializerTest extends EntitySerializerTestCase
{
    public function testInnerJoinForFirstLevelAssociation(): void
    {
        $qb = $this->em->getRepository(Product::class)->createQueryBuilder('e')
            ->where('e.id = :id')
            ->setParameter('id', 1);

        $this->setQueryExpectation(
            $this->getDriverConnectionMock($this->em),
            'SELECT p0_.id AS id_0,'
            . ' u1_.id AS id_1, u1_.name AS name_2'
            . ' FROM product_table p0_'
            . ' INNER JOIN user_table u1_ ON p0_.owner_id = u1_.id'
            . ' WHERE p0_.id = ?',
            [
                [
                    'id_0'   => 1,
                    'id_1'   => 10,
                    'name_2' => 'user_name'
                ]
            ],
            [1 => 1],
            [1 => \PDO::PARAM_INT]
        );

        $result = $this->serializer->serialize(
            $qb,
            [
                'inner_join_associations' => ['owner'],
                'exclusion_policy'        => 'all',
                'fields'                  => [
                    'ownerName' => [
                        'property_path' => 'owner.name'
                    ]
                ]
            ]
        );

        $this->assertArrayEquals(
            [
                [
                    'ownerName' => 'user_name',
                    'owner'     => [
                        'id'   => 10,
                        'name' => 'user_name'
                    ]
                ]
            ],
            $result
        );
    }

    public function testInnerJoinForFirstLevelAssociationAndHasLeftJoinForSecondLevelAssociation(): void
    {
        $qb = $this->em->getRepository(Product::class)->createQueryBuilder('e')
            ->where('e.id = :id')
            ->setParameter('id', 1);

        $this->addQueryExpectation(
            'SELECT p0_.id AS id_0,'
            . ' u1_.id AS id_1, u1_.name AS name_2,'
            . ' p0_.category_name AS category_name_3, p0_.owner_id AS owner_id_4,'
            . ' u1_.category_name AS category_name_5'
            . ' FROM product_table p0_'
            . ' INNER JOIN user_table u1_ ON p0_.owner_id = u1_.id'
            . ' WHERE p0_.id = ?',
            [
                [
                    'id_0'            => 1,
                    'id_1'            => 10,
                    'name_2'          => 'user_name',
                    'category_name_3' => 'product_category_name',
                    'owner_id_4'      => 10,
                    'category_name_5' => 'user_category_name'
                ]
            ],
            [1 => 1],
            [1 => \PDO::PARAM_INT]
        );
        $this->addQueryExpectation(
            'SELECT t0.name AS name_1, t0.label AS label_2'
            . ' FROM category_table t0'
            . ' WHERE t0.name = ?',
            [
                [
                    'name_1'  => 'user_category_name',
                    'label_2' => 'user_category_label'
                ]
            ],
            [1 => 'user_category_name'],
            [1 => \PDO::PARAM_STR]
        );
        $this->applyQueryExpectations($this->getDriverConnectionMock($this->em));

        $result = $this->serializer->serialize(
            $qb,
            [
                'inner_join_associations' => ['owner'],
                'exclusion_policy'        => 'all',
                'fields'                  => [
                    'ownerName'     => [
                        'property_path' => 'owner.name'
                    ],
                    'ownerCategory' => [
                        'property_path' => 'owner.category.label'
                    ]
                ]
            ]
        );

        $this->assertArrayEquals(
            [
                [
                    'ownerName'     => 'user_name',
                    'ownerCategory' => 'user_category_label',
                    'owner'         => [
                        'id'       => 10,
                        'name'     => 'user_name',
                        'category' => [
                            'name'  => 'user_category_name',
                            'label' => 'user_category_label'
                        ]
                    ]
                ]
            ],
            $result
        );
    }

    public function testInnerJoinForFirstLevelAssociationAndLeftJoinAlreadyExists(): void
    {
        $qb = $this->em->getRepository(Product::class)->createQueryBuilder('e')
            ->leftJoin('e.owner', 'owner', 'WITH', 'owner.id > 0')
            ->where('e.id = :id')
            ->setParameter('id', 1);

        $this->setQueryExpectation(
            $this->getDriverConnectionMock($this->em),
            'SELECT p0_.id AS id_0,'
            . ' u1_.id AS id_1, u1_.name AS name_2'
            . ' FROM product_table p0_'
            . ' INNER JOIN user_table u1_ ON p0_.owner_id = u1_.id AND (u1_.id > 0)'
            . ' WHERE p0_.id = ?',
            [
                [
                    'id_0'   => 1,
                    'id_1'   => 10,
                    'name_2' => 'user_name'
                ]
            ],
            [1 => 1],
            [1 => \PDO::PARAM_INT]
        );

        $result = $this->serializer->serialize(
            $qb,
            [
                'inner_join_associations' => ['owner'],
                'exclusion_policy'        => 'all',
                'fields'                  => [
                    'ownerName' => [
                        'property_path' => 'owner.name'
                    ]
                ]
            ]
        );

        $this->assertArrayEquals(
            [
                [
                    'ownerName' => 'user_name',
                    'owner'     => [
                        'id'   => 10,
                        'name' => 'user_name'
                    ]
                ]
            ],
            $result
        );
    }

    public function testInnerJoinForFirstLevelAssociationAndDisabledPartialLoad(): void
    {
        $qb = $this->em->getRepository(Product::class)->createQueryBuilder('e')
            ->where('e.id = :id')
            ->setParameter('id', 1);

        $this->setQueryExpectation(
            $this->getDriverConnectionMock($this->em),
            'SELECT p0_.id AS id_0,'
            . ' p0_.name AS name_1,'
            . ' u1_.id AS id_2, u1_.name AS name_3,'
            . ' p0_.category_name AS category_name_4, p0_.owner_id AS owner_id_5,'
            . ' u1_.category_name AS category_name_6'
            . ' FROM product_table p0_'
            . ' INNER JOIN user_table u1_ ON p0_.owner_id = u1_.id'
            . ' WHERE p0_.id = ?',
            [
                [
                    'id_0'            => 1,
                    'name_1'          => 'product_name',
                    'id_2'            => 10,
                    'name_3'          => 'user_name',
                    'category_name_4' => 'product_category_name',
                    'owner_id_5'      => 10,
                    'category_name_6' => 'user_category_name'
                ]
            ],
            [1 => 1],
            [1 => \PDO::PARAM_INT]
        );

        $result = $this->serializer->serialize(
            $qb,
            [
                'inner_join_associations' => ['owner'],
                'disable_partial_load'    => true,
                'exclusion_policy'        => 'all',
                'fields'                  => [
                    'ownerName' => [
                        'property_path' => 'owner.name'
                    ]
                ]
            ]
        );

        $this->assertArrayEquals(
            [
                [
                    'ownerName' => 'user_name',
                    'owner'     => [
                        'id'   => 10,
                        'name' => 'user_name'
                    ]
                ]
            ],
            $result
        );
    }

    public function testInnerJoinForSecondLevelAssociation(): void
    {
        $qb = $this->em->getRepository(Product::class)->createQueryBuilder('e')
            ->where('e.id = :id')
            ->setParameter('id', 1);

        $this->setQueryExpectation(
            $this->getDriverConnectionMock($this->em),
            'SELECT p0_.id AS id_0,'
            . ' u1_.id AS id_1, u1_.name AS name_2,'
            . ' c2_.name AS name_3, c2_.label AS label_4'
            . ' FROM product_table p0_'
            . ' INNER JOIN user_table u1_ ON p0_.owner_id = u1_.id'
            . ' INNER JOIN category_table c2_ ON u1_.category_name = c2_.name'
            . ' WHERE p0_.id = ?',
            [
                [
                    'id_0'    => 1,
                    'id_1'    => 10,
                    'name_2'  => 'user_name',
                    'name_3'  => 'user_category_name',
                    'label_4' => 'user_category_label'
                ]
            ],
            [1 => 1],
            [1 => \PDO::PARAM_INT]
        );

        $result = $this->serializer->serialize(
            $qb,
            [
                'inner_join_associations' => ['owner.category'],
                'exclusion_policy'        => 'all',
                'fields'                  => [
                    'ownerCategory' => [
                        'property_path' => 'owner.category.label'
                    ]
                ]
            ]
        );

        $this->assertArrayEquals(
            [
                [
                    'ownerCategory' => 'user_category_label',
                    'owner'         => [
                        'id'       => 10,
                        'name'     => 'user_name',
                        'category' => [
                            'name'  => 'user_category_name',
                            'label' => 'user_category_label'
                        ]
                    ]
                ]
            ],
            $result
        );
    }

    public function testInnerJoinForSecondLevelAssociationAndFirstLevelAssociationIsRequested(): void
    {
        $qb = $this->em->getRepository(Product::class)->createQueryBuilder('e')
            ->where('e.id = :id')
            ->setParameter('id', 1);

        $this->setQueryExpectation(
            $this->getDriverConnectionMock($this->em),
            'SELECT p0_.id AS id_0,'
            . ' u1_.id AS id_1, u1_.name AS name_2,'
            . ' c2_.name AS name_3, c2_.label AS label_4'
            . ' FROM product_table p0_'
            . ' INNER JOIN user_table u1_ ON p0_.owner_id = u1_.id'
            . ' INNER JOIN category_table c2_ ON u1_.category_name = c2_.name'
            . ' WHERE p0_.id = ?',
            [
                [
                    'id_0'    => 1,
                    'id_1'    => 10,
                    'name_2'  => 'user_name',
                    'name_3'  => 'user_category_name',
                    'label_4' => 'user_category_label'
                ]
            ],
            [1 => 1],
            [1 => \PDO::PARAM_INT]
        );

        $result = $this->serializer->serialize(
            $qb,
            [
                'inner_join_associations' => ['owner.category'],
                'exclusion_policy'        => 'all',
                'fields'                  => [
                    'ownerName'     => [
                        'property_path' => 'owner.name'
                    ],
                    'ownerCategory' => [
                        'property_path' => 'owner.category.label'
                    ]
                ]
            ]
        );

        $this->assertArrayEquals(
            [
                [
                    'ownerName'     => 'user_name',
                    'ownerCategory' => 'user_category_label',
                    'owner'         => [
                        'id'       => 10,
                        'name'     => 'user_name',
                        'category' => [
                            'name'  => 'user_category_name',
                            'label' => 'user_category_label'
                        ]
                    ]
                ]
            ],
            $result
        );
    }

    public function testInnerJoinForSecondLevelAssociationAndDisabledPartialLoad(): void
    {
        $qb = $this->em->getRepository(Product::class)->createQueryBuilder('e')
            ->where('e.id = :id')
            ->setParameter('id', 1);

        $this->addQueryExpectation(
            'SELECT p0_.id AS id_0,'
            . ' p0_.name AS name_1,'
            . ' u1_.id AS id_2, u1_.name AS name_3,'
            . ' p0_.category_name AS category_name_4, p0_.owner_id AS owner_id_5,'
            . ' u1_.category_name AS category_name_6'
            . ' FROM product_table p0_'
            . ' INNER JOIN user_table u1_ ON p0_.owner_id = u1_.id'
            . ' WHERE p0_.id = ?',
            [
                [
                    'id_0'            => 1,
                    'name_1'          => 'product_name',
                    'id_2'            => 10,
                    'name_3'          => 'user_name',
                    'category_name_4' => 'product_category_name',
                    'owner_id_5'      => 10,
                    'category_name_6' => 'user_category_name'
                ]
            ],
            [1 => 1],
            [1 => \PDO::PARAM_INT]
        );
        $this->addQueryExpectation(
            'SELECT t0.name AS name_1, t0.label AS label_2'
            . ' FROM category_table t0'
            . ' WHERE t0.name = ?',
            [
                [
                    'name_1'  => 'user_category_name',
                    'label_2' => 'user_category_label'
                ]
            ],
            [1 => 'user_category_name'],
            [1 => \PDO::PARAM_STR]
        );
        $this->applyQueryExpectations($this->getDriverConnectionMock($this->em));

        $result = $this->serializer->serialize(
            $qb,
            [
                'inner_join_associations' => ['owner.category'],
                'disable_partial_load'    => true,
                'exclusion_policy'        => 'all',
                'fields'                  => [
                    'ownerCategory' => [
                        'property_path' => 'owner.category.label'
                    ]
                ]
            ]
        );

        $this->assertArrayEquals(
            [
                [
                    'ownerCategory' => 'user_category_label',
                    'owner'         => [
                        'id'       => 10,
                        'name'     => 'user_name',
                        'category' => [
                            'name'  => 'user_category_name',
                            'label' => 'user_category_label'
                        ]
                    ]
                ]
            ],
            $result
        );
    }
}
