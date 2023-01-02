<?php

namespace Oro\Component\EntitySerializer\Tests\Unit;

use Doctrine\ORM\Query\Expr\Join;
use Oro\Component\EntitySerializer\AssociationQuery;
use Oro\Component\EntitySerializer\Tests\Unit\Fixtures\Entity;

class ToManyWithCustomQueryEntitySerializerTest extends EntitySerializerTestCase
{
    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testToManyCustomAssociationWhenOnlyIdentifierFieldIsRequested(): void
    {
        $qb = $this->em->getRepository(Entity\Group::class)->createQueryBuilder('e')
            ->where('e.id IN (:ids)')
            ->setParameter('ids', [123, 456]);

        $this->addQueryExpectation(
            'SELECT g0_.id AS id_0, g0_.name AS name_1'
            . ' FROM group_table g0_'
            . ' WHERE g0_.id IN (?, ?)',
            [
                [
                    'id_0'   => 123,
                    'name_1' => 'group_name1'
                ],
                [
                    'id_0'   => 456,
                    'name_1' => 'group_name2'
                ]
            ],
            [1 => 123, 2 => 456],
            [1 => \PDO::PARAM_INT, 2 => \PDO::PARAM_INT]
        );
        $this->addQueryExpectation(
            'SELECT g0_.id AS id_0, u1_.id AS id_1'
            . ' FROM group_table g0_'
            . ' INNER JOIN user_table u1_ ON (EXISTS ('
            . 'SELECT 1 FROM rel_user_to_group_table r2_'
            . ' WHERE r2_.user_id = u1_.id AND r2_.user_group_id IN (g0_.id)) AND u1_.category_name = ?)'
            . ' WHERE g0_.id IN (?, ?)',
            [
                [
                    'id_0' => 123,
                    'id_1' => 10
                ],
                [
                    'id_0' => 123,
                    'id_1' => 20
                ]
            ],
            [1 => 1, 2 => 123, 3 => 456],
            [1 => \PDO::PARAM_INT, 2 => \PDO::PARAM_INT, 3 => \PDO::PARAM_INT]
        );
        $this->applyQueryExpectations($this->getDriverConnectionMock($this->em));

        $associationQb = $this->em->getRepository(Entity\Group::class)
            ->createQueryBuilder('e')
            ->innerJoin(
                Entity\User::class,
                'r',
                Join::WITH,
                'e MEMBER OF r.groups AND r.category = :category'
            )
            ->setParameter(':category', 1);

        $result = $this->serializer->serialize(
            $qb,
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'id'    => null,
                    'name'  => null,
                    'users' => [
                        'exclusion_policy'  => 'all',
                        'association_query' => new AssociationQuery($associationQb, Entity\User::class),
                        'fields'            => [
                            'id' => null
                        ]
                    ]
                ]
            ]
        );

        $this->assertArrayEquals(
            [
                [
                    'id'    => 123,
                    'name'  => 'group_name1',
                    'users' => [
                        ['id' => 10],
                        ['id' => 20]
                    ]
                ],
                [
                    'id'    => 456,
                    'name'  => 'group_name2',
                    'users' => []
                ]
            ],
            $result
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testToManyCustomAssociationWhenOnlyIdentifierFieldWithLimitIsRequested(): void
    {
        $qb = $this->em->getRepository(Entity\Group::class)->createQueryBuilder('e')
            ->where('e.id IN (:ids)')
            ->setParameter('ids', [123, 456]);

        $this->addQueryExpectation(
            'SELECT g0_.id AS id_0, g0_.name AS name_1'
            . ' FROM group_table g0_'
            . ' WHERE g0_.id IN (?, ?)',
            [
                [
                    'id_0'   => 123,
                    'name_1' => 'group_name1'
                ],
                [
                    'id_0'   => 456,
                    'name_1' => 'group_name2'
                ]
            ],
            [1 => 123, 2 => 456],
            [1 => \PDO::PARAM_INT, 2 => \PDO::PARAM_INT]
        );
        $this->addQueryExpectation(
            'SELECT entity.id_0 AS entityId, entity.id_1 AS relatedEntityId'
            . ' FROM ('
            . '(SELECT g0_.id AS id_0, u1_.id AS id_1 FROM group_table g0_'
            . ' INNER JOIN user_table u1_ ON (EXISTS ('
            . 'SELECT 1 FROM rel_user_to_group_table r2_'
            . ' WHERE r2_.user_id = u1_.id AND r2_.user_group_id IN (g0_.id)) AND u1_.category_name = 1)'
            . ' WHERE g0_.id = 123 LIMIT 5) '
            . 'UNION ALL '
            . '(SELECT g0_.id AS id_0, u1_.id AS id_1 FROM group_table g0_'
            . ' INNER JOIN user_table u1_ ON (EXISTS ('
            . 'SELECT 1 FROM rel_user_to_group_table r2_'
            . ' WHERE r2_.user_id = u1_.id AND r2_.user_group_id IN (g0_.id)) AND u1_.category_name = 1)'
            . ' WHERE g0_.id = 456 LIMIT 5)'
            . ') entity',
            [
                [
                    'entityId'        => '123',
                    'relatedEntityId' => '10'
                ],
                [
                    'entityId'        => '123',
                    'relatedEntityId' => '20'
                ]
            ]
        );
        $this->applyQueryExpectations($this->getDriverConnectionMock($this->em));

        $associationQb = $this->em->getRepository(Entity\Group::class)
            ->createQueryBuilder('e')
            ->innerJoin(
                Entity\User::class,
                'r',
                Join::WITH,
                'e MEMBER OF r.groups AND r.category = :category'
            )
            ->setParameter(':category', 1);

        $result = $this->serializer->serialize(
            $qb,
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'id'    => null,
                    'name'  => null,
                    'users' => [
                        'exclusion_policy'  => 'all',
                        'association_query' => new AssociationQuery($associationQb, Entity\User::class),
                        'max_results'       => 5,
                        'fields'            => [
                            'id' => null
                        ]
                    ]
                ]
            ]
        );

        $this->assertArrayEquals(
            [
                [
                    'id'    => 123,
                    'name'  => 'group_name1',
                    'users' => [
                        ['id' => 10],
                        ['id' => 20]
                    ]
                ],
                [
                    'id'    => 456,
                    'name'  => 'group_name2',
                    'users' => []
                ]
            ],
            $result
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testToManyCustomAssociationWhenOnlyScalarFieldsAreRequested(): void
    {
        $qb = $this->em->getRepository(Entity\Group::class)->createQueryBuilder('e')
            ->where('e.id IN (:ids)')
            ->setParameter('ids', [123, 456]);

        $this->addQueryExpectation(
            'SELECT g0_.id AS id_0, g0_.name AS name_1'
            . ' FROM group_table g0_'
            . ' WHERE g0_.id IN (?, ?)',
            [
                [
                    'id_0'   => 123,
                    'name_1' => 'group_name1'
                ],
                [
                    'id_0'   => 456,
                    'name_1' => 'group_name2'
                ]
            ],
            [1 => 123, 2 => 456],
            [1 => \PDO::PARAM_INT, 2 => \PDO::PARAM_INT]
        );
        $this->addQueryExpectation(
            'SELECT g0_.id AS id_0, u1_.id AS id_1, u1_.name AS name_2'
            . ' FROM group_table g0_'
            . ' INNER JOIN user_table u1_ ON (EXISTS ('
            . 'SELECT 1 FROM rel_user_to_group_table r2_'
            . ' WHERE r2_.user_id = u1_.id AND r2_.user_group_id IN (g0_.id)) AND u1_.category_name = ?)'
            . ' WHERE g0_.id IN (?, ?)',
            [
                [
                    'id_0'   => 123,
                    'id_1'   => 10,
                    'name_2' => 'user_name1'
                ],
                [
                    'id_0'   => 123,
                    'id_1'   => 20,
                    'name_2' => 'user_name2'
                ]
            ],
            [1 => 1, 2 => 123, 3 => 456],
            [1 => \PDO::PARAM_INT, 2 => \PDO::PARAM_INT, 3 => \PDO::PARAM_INT]
        );
        $this->applyQueryExpectations($this->getDriverConnectionMock($this->em));

        $associationQb = $this->em->getRepository(Entity\Group::class)
            ->createQueryBuilder('e')
            ->innerJoin(
                Entity\User::class,
                'r',
                Join::WITH,
                'e MEMBER OF r.groups AND r.category = :category'
            )
            ->setParameter(':category', 1);

        $result = $this->serializer->serialize(
            $qb,
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'id'    => null,
                    'name'  => null,
                    'users' => [
                        'exclusion_policy'  => 'all',
                        'association_query' => new AssociationQuery($associationQb, Entity\User::class),
                        'fields'            => [
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
                    'id'    => 123,
                    'name'  => 'group_name1',
                    'users' => [
                        ['id' => 10, 'name' => 'user_name1'],
                        ['id' => 20, 'name' => 'user_name2']
                    ]
                ],
                [
                    'id'    => 456,
                    'name'  => 'group_name2',
                    'users' => []
                ]
            ],
            $result
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testToManyCustomAssociationWhenOnlyScalarFieldsWithLimitAreRequested(): void
    {
        $qb = $this->em->getRepository(Entity\Group::class)->createQueryBuilder('e')
            ->where('e.id IN (:ids)')
            ->setParameter('ids', [123, 456]);

        $this->addQueryExpectation(
            'SELECT g0_.id AS id_0, g0_.name AS name_1'
            . ' FROM group_table g0_'
            . ' WHERE g0_.id IN (?, ?)',
            [
                [
                    'id_0'   => 123,
                    'name_1' => 'group_name1'
                ],
                [
                    'id_0'   => 456,
                    'name_1' => 'group_name2'
                ]
            ],
            [1 => 123, 2 => 456],
            [1 => \PDO::PARAM_INT, 2 => \PDO::PARAM_INT]
        );
        $this->addQueryExpectation(
            'SELECT entity.id_0 AS entityId, entity.id_1 AS relatedEntityId'
            . ' FROM ('
            . '(SELECT g0_.id AS id_0, u1_.id AS id_1 FROM group_table g0_'
            . ' INNER JOIN user_table u1_ ON (EXISTS ('
            . 'SELECT 1 FROM rel_user_to_group_table r2_'
            . ' WHERE r2_.user_id = u1_.id AND r2_.user_group_id IN (g0_.id)) AND u1_.category_name = 1)'
            . ' WHERE g0_.id = 123 LIMIT 5) '
            . 'UNION ALL '
            . '(SELECT g0_.id AS id_0, u1_.id AS id_1 FROM group_table g0_'
            . ' INNER JOIN user_table u1_ ON (EXISTS ('
            . 'SELECT 1 FROM rel_user_to_group_table r2_'
            . ' WHERE r2_.user_id = u1_.id AND r2_.user_group_id IN (g0_.id)) AND u1_.category_name = 1)'
            . ' WHERE g0_.id = 456 LIMIT 5)'
            . ') entity',
            [
                [
                    'entityId'        => '123',
                    'relatedEntityId' => '10'
                ],
                [
                    'entityId'        => '123',
                    'relatedEntityId' => '20'
                ]
            ]
        );
        $this->addQueryExpectation(
            'SELECT u0_.id AS id_0, u0_.name AS name_1'
            . ' FROM user_table u0_'
            . ' WHERE u0_.id IN (?, ?)',
            [
                [
                    'id_0'   => 10,
                    'name_1' => 'user_name1'
                ],
                [
                    'id_0'   => 20,
                    'name_1' => 'user_name2'
                ]
            ],
            [1 => 10, 2 => 20],
            [1 => \PDO::PARAM_INT, 2 => \PDO::PARAM_INT]
        );
        $this->applyQueryExpectations($this->getDriverConnectionMock($this->em));

        $associationQb = $this->em->getRepository(Entity\Group::class)
            ->createQueryBuilder('e')
            ->innerJoin(
                Entity\User::class,
                'r',
                Join::WITH,
                'e MEMBER OF r.groups AND r.category = :category'
            )
            ->setParameter(':category', 1);

        $result = $this->serializer->serialize(
            $qb,
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'id'    => null,
                    'name'  => null,
                    'users' => [
                        'exclusion_policy'  => 'all',
                        'association_query' => new AssociationQuery($associationQb, Entity\User::class),
                        'max_results'       => 5,
                        'fields'            => [
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
                    'id'    => 123,
                    'name'  => 'group_name1',
                    'users' => [
                        ['id' => 10, 'name' => 'user_name1'],
                        ['id' => 20, 'name' => 'user_name2']
                    ]
                ],
                [
                    'id'    => 456,
                    'name'  => 'group_name2',
                    'users' => []
                ]
            ],
            $result
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testToManyCustomAssociationWithToOneCustomAssociation(): void
    {
        $qb = $this->em->getRepository(Entity\Group::class)->createQueryBuilder('e')
            ->where('e.id IN (:ids)')
            ->setParameter('ids', [123, 456]);

        $this->addQueryExpectation(
            'SELECT g0_.id AS id_0, g0_.name AS name_1'
            . ' FROM group_table g0_'
            . ' WHERE g0_.id IN (?, ?)',
            [
                ['id_0' => 123, 'name_1' => 'group_name1'],
                ['id_0' => 456, 'name_1' => 'group_name2']
            ],
            [1 => 123, 2 => 456],
            [1 => \PDO::PARAM_INT, 2 => \PDO::PARAM_INT]
        );
        $this->addQueryExpectation(
            'SELECT g0_.id AS id_0, u1_.id AS id_1'
            . ' FROM group_table g0_'
            . ' INNER JOIN user_table u1_ ON (EXISTS ('
            . 'SELECT 1 FROM rel_user_to_group_table r2_'
            . ' WHERE r2_.user_id = u1_.id AND r2_.user_group_id IN (g0_.id)))'
            . ' WHERE g0_.id IN (?, ?)',
            [
                ['id_0' => 123, 'id_1' => 10],
                ['id_0' => 123, 'id_1' => 20]
            ],
            [1 => 123, 2 => 456],
            [1 => \PDO::PARAM_INT, 2 => \PDO::PARAM_INT]
        );
        $this->addQueryExpectation(
            'SELECT u0_.id AS id_0 FROM user_table u0_ WHERE u0_.id IN (?, ?)',
            [
                ['id_0' => 10],
                ['id_0' => 20]
            ],
            [1 => 10, 2 => 20],
            [1 => \PDO::PARAM_INT, 2 => \PDO::PARAM_INT]
        );
        $this->addQueryExpectation(
            'SELECT u0_.id AS id_0, c1_.name AS name_1'
            . ' FROM user_table u0_'
            . ' INNER JOIN category_table c1_ ON u0_.category_name = c1_.name'
            . ' WHERE u0_.id IN (?, ?)',
            [
                ['id_0' => 10, 'name_1' => 'category1'],
                ['id_0' => 20, 'name_1' => 'category2']
            ],
            [1 => 10, 2 => 20],
            [1 => \PDO::PARAM_INT, 2 => \PDO::PARAM_INT]
        );
        $this->applyQueryExpectations($this->getDriverConnectionMock($this->em));

        $associationQb = $this->em->getRepository(Entity\Group::class)
            ->createQueryBuilder('e')
            ->innerJoin(Entity\User::class, 'r', Join::WITH, 'e MEMBER OF r.groups');
        $categoryAssociationQb = $this->em->getRepository(Entity\User::class)
            ->createQueryBuilder('e')
            ->innerJoin('e.category', 'r');

        $result = $this->serializer->serialize(
            $qb,
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'id'    => null,
                    'name'  => null,
                    'users' => [
                        'exclusion_policy'  => 'all',
                        'association_query' => new AssociationQuery($associationQb, Entity\User::class),
                        'fields'            => [
                            'id'       => null,
                            'category' => [
                                'exclusion_policy'  => 'all',
                                'association_query' => new AssociationQuery(
                                    $categoryAssociationQb,
                                    Entity\Category::class,
                                    false
                                ),
                                'property_path'     => '_',
                                'target_class'      => Entity\Category::class,
                                'fields'            => [
                                    'name'  => null,
                                    'label' => ['exclude' => true]
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
                    'id'    => 123,
                    'name'  => 'group_name1',
                    'users' => [
                        [
                            'id'       => 10,
                            'category' => ['name' => 'category1']
                        ],
                        [
                            'id'       => 20,
                            'category' => ['name' => 'category2']
                        ]
                    ]
                ],
                [
                    'id'    => 456,
                    'name'  => 'group_name2',
                    'users' => []
                ]
            ],
            $result
        );
    }
}
