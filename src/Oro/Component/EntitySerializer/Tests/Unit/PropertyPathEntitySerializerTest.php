<?php

namespace Oro\Component\EntitySerializer\Tests\Unit;

class PropertyPathEntitySerializerTest extends EntitySerializerTestCase
{
    public function testRename()
    {
        $qb = $this->em->getRepository('Test:Group')->createQueryBuilder('e')
            ->where('e.id = :id')
            ->setParameter('id', 1);

        $this->setQueryExpectation(
            $this->getDriverConnectionMock($this->em),
            'SELECT g0_.id AS id_0, g0_.name AS name_1'
            . ' FROM group_table g0_'
            . ' WHERE g0_.id = ?',
            [
                [
                    'id_0'   => 1,
                    'name_1' => 'group_name',
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
                    'id'      => null,
                    'newName' => [
                        'property_path' => 'name'
                    ],
                ],
            ]
        );

        $this->assertArrayEquals(
            [
                [
                    'id'      => 1,
                    'newName' => 'group_name',
                ]
            ],
            $result
        );
    }

    public function testMetadata()
    {
        $qb = $this->em->getRepository('Test:Group')->createQueryBuilder('e')
            ->where('e.id = :id')
            ->setParameter('id', 1);

        $this->setQueryExpectation(
            $this->getDriverConnectionMock($this->em),
            'SELECT g0_.id AS id_0'
            . ' FROM group_table g0_'
            . ' WHERE g0_.id = ?',
            [
                [
                    'id_0' => 1
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
                    'id'     => null,
                    'entity' => [
                        'property_path' => '__class__'
                    ],
                ],
            ]
        );

        $this->assertArrayEquals(
            [
                [
                    'id'     => 1,
                    'entity' => 'Oro\Component\EntitySerializer\Tests\Unit\Fixtures\Entity\Group',
                ]
            ],
            $result
        );
    }

    public function testPropertyPath()
    {
        $qb = $this->em->getRepository('Test:Product')->createQueryBuilder('e')
            ->where('e.id = :id')
            ->setParameter('id', 1);

        $this->setQueryExpectation(
            $this->getDriverConnectionMock($this->em),
            'SELECT p0_.id AS id_0, p0_.name AS name_1,'
            . ' u1_.id AS id_2, u1_.name AS name_3,'
            . ' c2_.name AS name_4, c2_.label AS label_5,'
            . ' p0_.category_name AS category_name_6, p0_.owner_id AS owner_id_7,'
            . ' u1_.category_name AS category_name_8'
            . ' FROM product_table p0_'
            . ' LEFT JOIN user_table u1_ ON p0_.owner_id = u1_.id'
            . ' LEFT JOIN category_table c2_ ON p0_.category_name = c2_.name'
            . ' WHERE p0_.id = ?',
            [
                [
                    'id_0'            => 1,
                    'name_1'          => 'product_name',
                    'id_2'            => 10,
                    'name_3'          => 'user_name',
                    'name_4'          => 'category_name',
                    'label_5'         => 'category_label',
                    'category_name_6' => 'category_name',
                    'owner_id_7'      => 10,
                    'category_name_8' => 'category_name',
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
                    'id'        => null,
                    'name'      => null,
                    'ownerName' => [
                        'property_path' => 'owner.name'
                    ],
                    'owner'     => [
                        'fields' => 'id'
                    ],
                    'category'  => [
                        'exclusion_policy' => 'all',
                        'fields'           => [
                            'name' => [
                                'property_path' => 'label'
                            ]
                        ]
                    ]
                ],
            ]
        );

        $this->assertArrayEquals(
            [
                [
                    'id'        => 1,
                    'name'      => 'product_name',
                    'ownerName' => 'user_name',
                    'owner'     => 10,
                    'category'  => [
                        'name' => 'category_label'
                    ],
                ]
            ],
            $result
        );
    }

    public function testPropertyPathWithExclusion()
    {
        $qb = $this->em->getRepository('Test:Product')->createQueryBuilder('e')
            ->where('e.id = :id')
            ->setParameter('id', 1);

        $this->setQueryExpectation(
            $this->getDriverConnectionMock($this->em),
            'SELECT p0_.id AS id_0, p0_.name AS name_1,'
            . ' u1_.id AS id_2, u1_.name AS name_3,'
            . ' c2_.name AS name_4, c2_.label AS label_5,'
            . ' p0_.category_name AS category_name_6, p0_.owner_id AS owner_id_7,'
            . ' u1_.category_name AS category_name_8'
            . ' FROM product_table p0_'
            . ' LEFT JOIN user_table u1_ ON p0_.owner_id = u1_.id'
            . ' LEFT JOIN category_table c2_ ON p0_.category_name = c2_.name'
            . ' WHERE p0_.id = ?',
            [
                [
                    'id_0'            => 1,
                    'name_1'          => 'product_name',
                    'id_2'            => 10,
                    'name_3'          => 'user_name',
                    'name_4'          => 'category_name',
                    'label_5'         => 'category_label',
                    'category_name_6' => 'category_name',
                    'owner_id_7'      => 10,
                    'category_name_8' => 'category_name',
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
                    'id'        => null,
                    'name'      => null,
                    'ownerName' => [
                        'property_path' => 'owner.name'
                    ],
                    'owner'     => [
                        'exclude' => true
                    ],
                    'category'  => [
                        'exclusion_policy' => 'all',
                        'fields'           => [
                            'name' => [
                                'property_path' => 'label'
                            ]
                        ]
                    ]
                ],
            ]
        );

        $this->assertArrayEquals(
            [
                [
                    'id'        => 1,
                    'name'      => 'product_name',
                    'ownerName' => 'user_name',
                    'owner'     => [
                        'id' => 10
                    ],
                    'category'  => [
                        'name' => 'category_label'
                    ],
                ]
            ],
            $result
        );
    }
}
