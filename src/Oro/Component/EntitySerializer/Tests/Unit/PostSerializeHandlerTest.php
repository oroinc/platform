<?php

namespace Oro\Component\EntitySerializer\Tests\Unit;

use Oro\Component\EntitySerializer\Tests\Unit\Fixtures\Entity\User;

class PostSerializeHandlerTest extends EntitySerializerTestCase
{
    public function postSerializeCallback(array $item, array $context): array
    {
        $item['additional'] = sprintf('%s_additional[%s]', $item['name'] ?? '', $context['key']);

        return $item;
    }

    public function postSerializeCollectionCallback(array $items, array $context): array
    {
        $ids = [];
        foreach ($items as $item) {
            $ids[] = array_key_exists('id', $item)
                ? $item['id']
                : $item['name'];
        }
        $ids = implode(',', $ids);
        foreach ($items as $key => $item) {
            $item['collection'] = sprintf('%s [%s]', $ids, $context['key']);
            $items[$key] = $item;
        }

        return $items;
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testSimpleEntityWithPostSerializeAsClosure(): void
    {
        $qb = $this->em->getRepository(User::class)->createQueryBuilder('e')
            ->where('e.id = :id')
            ->setParameter('id', 1);

        $this->addQueryExpectation(
            'SELECT u0_.id AS id_0, u0_.name AS name_1,'
            . ' c1_.name AS name_2, c1_.label AS label_3'
            . ' FROM user_table u0_'
            . ' LEFT JOIN category_table c1_ ON u0_.category_name = c1_.name'
            . ' WHERE u0_.id = ?',
            [
                [
                    'id_0'    => 1,
                    'name_1'  => 'user_name',
                    'name_2'  => 'category_name',
                    'label_3' => 'category_label'
                ]
            ],
            [1 => 1],
            [1 => \PDO::PARAM_INT]
        );
        $this->addQueryExpectation(
            'SELECT u0_.id AS id_0, p1_.name AS name_1, p1_.id AS id_2'
            . ' FROM product_table p1_'
            . ' INNER JOIN user_table u0_ ON p1_.owner_id = u0_.id'
            . ' WHERE u0_.id = ?',
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
        $this->applyQueryExpectations($this->getDriverConnectionMock($this->em));

        $result = $this->serializer->serialize(
            $qb,
            [
                'exclusion_policy'          => 'all',
                'fields'                    => [
                    'id'       => null,
                    'name'     => null,
                    'category' => [
                        'exclusion_policy'          => 'all',
                        'fields'                    => [
                            'label' => null
                        ],
                        'post_serialize'            => function (array $result, array $context) {
                            return $this->postSerializeCallback($result, $context);
                        },
                        'post_serialize_collection' => function (array $result, array $context) {
                            return $this->postSerializeCollectionCallback($result, $context);
                        }
                    ],
                    'products' => [
                        'exclusion_policy'          => 'all',
                        'fields'                    => [
                            'name' => null
                        ],
                        'post_serialize'            => function (array $result, array $context) {
                            return $this->postSerializeCallback($result, $context);
                        },
                        'post_serialize_collection' => function (array $result, array $context) {
                            return $this->postSerializeCollectionCallback($result, $context);
                        }
                    ]
                ],
                'post_serialize'            => function (array $result, array $context) {
                    return $this->postSerializeCallback($result, $context);
                },
                'post_serialize_collection' => function (array $result, array $context) {
                    return $this->postSerializeCollectionCallback($result, $context);
                }
            ],
            ['key' => 'context value']
        );

        $this->assertArrayEquals(
            [
                [
                    'id'         => 1,
                    'name'       => 'user_name',
                    'category'   => [
                        'label'      => 'category_label',
                        'additional' => 'category_name_additional[context value]',
                        'collection' => 'category_name [context value]'
                    ],
                    'products'   => [
                        [
                            'name'       => 'product_name',
                            'additional' => 'product_name_additional[context value]',
                            'collection' => '10 [context value]'
                        ]
                    ],
                    'additional' => 'user_name_additional[context value]',
                    'collection' => '1 [context value]'
                ]
            ],
            $result
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testSimpleEntityWithPostSerializeAsCallable(): void
    {
        $qb = $this->em->getRepository(User::class)->createQueryBuilder('e')
            ->where('e.id = :id')
            ->setParameter('id', 1);

        $this->addQueryExpectation(
            'SELECT u0_.id AS id_0, u0_.name AS name_1,'
            . ' c1_.name AS name_2, c1_.label AS label_3'
            . ' FROM user_table u0_'
            . ' LEFT JOIN category_table c1_ ON u0_.category_name = c1_.name'
            . ' WHERE u0_.id = ?',
            [
                [
                    'id_0'    => 1,
                    'name_1'  => 'user_name',
                    'name_2'  => 'category_name',
                    'label_3' => 'category_label'
                ]
            ],
            [1 => 1],
            [1 => \PDO::PARAM_INT]
        );
        $this->addQueryExpectation(
            'SELECT u0_.id AS id_0, p1_.name AS name_1, p1_.id AS id_2'
            . ' FROM product_table p1_'
            . ' INNER JOIN user_table u0_ ON p1_.owner_id = u0_.id'
            . ' WHERE u0_.id = ?',
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
        $this->applyQueryExpectations($this->getDriverConnectionMock($this->em));

        $result = $this->serializer->serialize(
            $qb,
            [
                'exclusion_policy'          => 'all',
                'fields'                    => [
                    'id'       => null,
                    'name'     => null,
                    'category' => [
                        'exclusion_policy'          => 'all',
                        'fields'                    => [
                            'label' => null
                        ],
                        'post_serialize'            => [$this, 'postSerializeCallback'],
                        'post_serialize_collection' => [$this, 'postSerializeCollectionCallback']
                    ],
                    'products' => [
                        'exclusion_policy'          => 'all',
                        'fields'                    => [
                            'name' => null
                        ],
                        'post_serialize'            => [$this, 'postSerializeCallback'],
                        'post_serialize_collection' => [$this, 'postSerializeCollectionCallback']
                    ]
                ],
                'post_serialize'            => [$this, 'postSerializeCallback'],
                'post_serialize_collection' => [$this, 'postSerializeCollectionCallback']
            ],
            ['key' => 'context value']
        );

        $this->assertArrayEquals(
            [
                [
                    'id'         => 1,
                    'name'       => 'user_name',
                    'category'   => [
                        'label'      => 'category_label',
                        'additional' => 'category_name_additional[context value]',
                        'collection' => 'category_name [context value]'
                    ],
                    'products'   => [
                        [
                            'name'       => 'product_name',
                            'additional' => 'product_name_additional[context value]',
                            'collection' => '10 [context value]'
                        ]
                    ],
                    'additional' => 'user_name_additional[context value]',
                    'collection' => '1 [context value]'
                ]
            ],
            $result
        );
    }

    public function testPostSerializeForNullChild(): void
    {
        $qb = $this->em->getRepository(User::class)->createQueryBuilder('e')
            ->where('e.id = :id')
            ->setParameter('id', 1);

        $this->setQueryExpectation(
            $this->getDriverConnectionMock($this->em),
            'SELECT u0_.id AS id_0, u0_.name AS name_1,'
            . ' c1_.name AS name_2'
            . ' FROM user_table u0_'
            . ' LEFT JOIN category_table c1_ ON u0_.category_name = c1_.name'
            . ' WHERE u0_.id = ?',
            [
                [
                    'id_0'   => 1,
                    'name_1' => 'user_name',
                    'name_2' => null
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
                            'name' => null
                        ],
                        'post_serialize'   => [$this, 'postSerializeCallback']
                    ]
                ]
            ]
        );

        $this->assertArrayEquals(
            [
                [
                    'id'       => 1,
                    'name'     => 'user_name',
                    'category' => null
                ]
            ],
            $result
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testManyToManyBidirectionalWithPostSerialize(): void
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
                'exclusion_policy'          => 'all',
                'post_serialize'            => [$this, 'postSerializeCallback'],
                'post_serialize_collection' => [$this, 'postSerializeCollectionCallback'],
                'fields'                    => [
                    'id'       => null,
                    'name'     => null,
                    'products' => [
                        'exclusion_policy'          => 'all',
                        'post_serialize'            => [$this, 'postSerializeCallback'],
                        'post_serialize_collection' => [$this, 'postSerializeCollectionCallback'],
                        'fields'                    => [
                            'id'     => null,
                            'name'   => null,
                            'groups' => [
                                'post_serialize'            => [$this, 'postSerializeCallback'],
                                'post_serialize_collection' => [$this, 'postSerializeCollectionCallback'],
                                'fields'                    => 'id'
                            ]
                        ]
                    ]
                ]
            ],
            ['key' => 'context value']
        );

        $this->assertArrayEquals(
            [
                [
                    'id'         => 1,
                    'name'       => 'user_name',
                    'products'   => [
                        [
                            'id'         => 10,
                            'name'       => 'product_name1',
                            'groups'     => [100],
                            'additional' => 'product_name1_additional[context value]',
                            'collection' => '10,20 [context value]'
                        ],
                        [
                            'id'         => 20,
                            'name'       => 'product_name2',
                            'groups'     => [200, 201],
                            'additional' => 'product_name2_additional[context value]',
                            'collection' => '10,20 [context value]'
                        ]
                    ],
                    'additional' => 'user_name_additional[context value]',
                    'collection' => '1 [context value]'
                ]
            ],
            $result
        );
    }
}
