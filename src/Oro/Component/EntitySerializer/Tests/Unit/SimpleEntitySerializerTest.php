<?php

namespace Oro\Component\EntitySerializer\Tests\Unit;

use Oro\Component\EntitySerializer\Tests\Unit\Fixtures\Entity\Group;
use Oro\Component\EntitySerializer\Tests\Unit\Fixtures\Entity\Product;
use Oro\Component\EntitySerializer\Tests\Unit\Fixtures\Entity\User;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class SimpleEntitySerializerTest extends EntitySerializerTestCase
{
    public function testReuseExistingJoin(): void
    {
        $qb = $this->em->getRepository(Product::class)->createQueryBuilder('e')
            ->leftJoin('e.owner', 'user')
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

    public function testReuseExistingInnerJoin(): void
    {
        $qb = $this->em->getRepository(Product::class)->createQueryBuilder('e')
            ->innerJoin('e.owner', 'user')
            ->where('e.id = :id')
            ->setParameter('id', 1);

        $this->setQueryExpectation(
            $this->getDriverConnectionMock($this->em),
            'SELECT p0_.id AS id_0, p0_.name AS name_1,'
            . ' u1_.id AS id_2'
            . ' FROM product_table p0_'
            . ' INNER JOIN user_table u1_ ON p0_.owner_id = u1_.id'
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

    public function testReuseExistingJoinWithCondition(): void
    {
        $qb = $this->em->getRepository(Product::class)->createQueryBuilder('e')
            ->leftJoin('e.owner', 'user', 'WITH', 'e.owner = user.id AND user.name LIKE \'a%\'')
            ->where('e.id = :id')
            ->setParameter('id', 1);

        $this->setQueryExpectation(
            $this->getDriverConnectionMock($this->em),
            'SELECT p0_.id AS id_0, p0_.name AS name_1,'
            . ' u1_.id AS id_2'
            . ' FROM product_table p0_'
            . ' LEFT JOIN user_table u1_ ON p0_.owner_id = u1_.id AND (p0_.owner_id = u1_.id AND u1_.name LIKE \'a%\')'
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

    public function testSimpleEntityWithoutConfig(): void
    {
        $qb = $this->em->getRepository(Group::class)->createQueryBuilder('e')
            ->where('e.id = :id')
            ->setParameter('id', 1);

        $this->setQueryExpectation(
            $this->getDriverConnectionMock($this->em),
            'SELECT g0_.id AS id_0, g0_.name AS name_1, g0_.label AS label_2'
            . ', g0_.public AS public_3, g0_.is_exception AS is_exception_4'
            . ' FROM group_table g0_'
            . ' WHERE g0_.id = ?',
            [
                [
                    'id_0'           => 1,
                    'name_1'         => 'test_name',
                    'label_2'        => 'test_label',
                    'public_3'       => 1,
                    'is_exception_4' => 0
                ]
            ],
            [1 => 1],
            [1 => \PDO::PARAM_INT]
        );

        $result = $this->serializer->serialize($qb, []);

        $this->assertArrayEquals(
            [
                [
                    'id'          => 1,
                    'name'        => 'test_name',
                    'label'       => 'test_label',
                    'public'      => true,
                    'isException' => false
                ]
            ],
            $result
        );
    }

    public function testSimpleEntityWithExclusion(): void
    {
        $qb = $this->em->getRepository(Group::class)->createQueryBuilder('e')
            ->where('e.id = :id')
            ->setParameter('id', 1);

        $this->setQueryExpectation(
            $this->getDriverConnectionMock($this->em),
            'SELECT g0_.id AS id_0, g0_.label AS label_1, g0_.public AS public_2'
            . ' FROM group_table g0_'
            . ' WHERE g0_.id = ?',
            [
                [
                    'id_0'     => 1,
                    'label_1'  => 'test_label',
                    'public_2' => 1
                ]
            ],
            [1 => 1],
            [1 => \PDO::PARAM_INT]
        );

        $result = $this->serializer->serialize(
            $qb,
            [
                'fields' => [
                    'name'        => ['exclude' => true],
                    'isException' => ['exclude' => true]
                ]
            ]
        );

        $this->assertArrayEquals(
            [
                [
                    'id'     => 1,
                    'label'  => 'test_label',
                    'public' => true
                ]
            ],
            $result
        );
    }

    public function testSimpleEntityWithComputedField(): void
    {
        $qb = $this->em->getRepository(Group::class)->createQueryBuilder('e')
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
                    'name_1' => 'test_name'
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
                    'id'           => null,
                    'name'         => null,
                    'computedName' => null
                ]
            ]
        );

        $this->assertArrayEquals(
            [
                [
                    'id'           => 1,
                    'name'         => 'test_name',
                    'computedName' => 'test_name (COMPUTED)'
                ]
            ],
            $result
        );
    }

    public function testSimpleEntityWithExclusionAndPartialLoadDisabled(): void
    {
        $qb = $this->em->getRepository(Group::class)->createQueryBuilder('e')
            ->where('e.id = :id')
            ->setParameter('id', 1);

        $this->setQueryExpectation(
            $this->getDriverConnectionMock($this->em),
            'SELECT g0_.id AS id_0, g0_.name AS name_1, g0_.label AS label_2'
            . ', g0_.public AS public_3, g0_.is_exception AS is_exception_4'
            . ' FROM group_table g0_'
            . ' WHERE g0_.id = ?',
            [
                [
                    'id_0'           => 1,
                    'name_1'         => 'test_name',
                    'label_2'        => 'test_label',
                    'public_3'       => 1,
                    'is_exception_4' => 0
                ]
            ],
            [1 => 1],
            [1 => \PDO::PARAM_INT]
        );

        $result = $this->serializer->serialize(
            $qb,
            [
                'disable_partial_load' => true,
                'fields'               => [
                    'name'        => [
                        'exclude' => true
                    ],
                    'isException' => [
                        'exclude' => true
                    ]
                ]
            ]
        );

        $this->assertArrayEquals(
            [
                [
                    'id'     => 1,
                    'label'  => 'test_label',
                    'public' => true
                ]
            ],
            $result
        );
    }

    public function testSimpleEntityWithSpecifiedFieldsButNoExclusionPolicy(): void
    {
        $qb = $this->em->getRepository(Group::class)->createQueryBuilder('e')
            ->where('e.id = :id')
            ->setParameter('id', 1);

        $this->setQueryExpectation(
            $this->getDriverConnectionMock($this->em),
            'SELECT g0_.id AS id_0, g0_.name AS name_1, g0_.label AS label_2'
            . ', g0_.public AS public_3, g0_.is_exception AS is_exception_4'
            . ' FROM group_table g0_'
            . ' WHERE g0_.id = ?',
            [
                [
                    'id_0'           => 1,
                    'name_1'         => 'test_name',
                    'label_2'        => null,
                    'public_3'       => 0,
                    'is_exception_4' => 0
                ]
            ],
            [1 => 1],
            [1 => \PDO::PARAM_INT]
        );

        $result = $this->serializer->serialize(
            $qb,
            [
                'fields' => [
                    'id'   => null,
                    'name' => null
                ]
            ]
        );

        $this->assertArrayEquals(
            [
                [
                    'id'          => 1,
                    'name'        => 'test_name',
                    'label'       => null,
                    'public'      => false,
                    'isException' => false
                ]
            ],
            $result
        );
    }

    public function testSimpleEntityWithSpecifiedFieldsOnly(): void
    {
        $qb = $this->em->getRepository(Group::class)->createQueryBuilder('e')
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
                    'name_1' => 'test_name'
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
                    'id'   => null,
                    'name' => null
                ]
            ]
        );

        $this->assertArrayEquals(
            [
                [
                    'id'   => 1,
                    'name' => 'test_name'
                ]
            ],
            $result
        );
    }

    public function testSimpleEntityWithSpecifiedFieldsAndExclusions(): void
    {
        $qb = $this->em->getRepository(Group::class)->createQueryBuilder('e')
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
                    'id'   => null,
                    'name' => [
                        'exclude' => true
                    ]
                ]
            ]
        );

        $this->assertArrayEquals(
            [
                [
                    'id' => 1
                ]
            ],
            $result
        );
    }

    public function testSimpleEntityWithMetadata(): void
    {
        $qb = $this->em->getRepository(Group::class)->createQueryBuilder('e')
            ->where('e.id = :id')
            ->setParameter('id', 1);

        $this->setQueryExpectation(
            $this->getDriverConnectionMock($this->em),
            'SELECT g0_.id AS id_0, g0_.label AS label_1'
            . ' FROM group_table g0_'
            . ' WHERE g0_.id = ?',
            [
                [
                    'id_0'    => 1,
                    'label_1' => 'test_label'
                ]
            ],
            [1 => 1],
            [1 => \PDO::PARAM_INT]
        );

        $result = $this->serializer->serialize(
            $qb,
            [
                'fields' => [
                    'entity'      => [
                        'property_path' => '__class__'
                    ],
                    'name'        => [
                        'exclude' => true
                    ],
                    'public'      => [
                        'exclude' => true
                    ],
                    'isException' => [
                        'exclude' => true
                    ]
                ]
            ]
        );

        $this->assertArrayEquals(
            [
                [
                    'id'     => 1,
                    'label'  => 'test_label',
                    'entity' => Group::class
                ]
            ],
            $result
        );
    }

    public function testSimpleEntityWithMetadataWithoutPropertyPath(): void
    {
        $qb = $this->em->getRepository(Group::class)->createQueryBuilder('e')
            ->where('e.id = :id')
            ->setParameter('id', 1);

        $this->setQueryExpectation(
            $this->getDriverConnectionMock($this->em),
            'SELECT g0_.id AS id_0, g0_.label AS label_1'
            . ' FROM group_table g0_'
            . ' WHERE g0_.id = ?',
            [
                [
                    'id_0'    => 1,
                    'label_1' => 'test_label'
                ]
            ],
            [1 => 1],
            [1 => \PDO::PARAM_INT]
        );

        $result = $this->serializer->serialize(
            $qb,
            [
                'fields' => [
                    '__class__'   => null,
                    'name'        => [
                        'exclude' => true
                    ],
                    'public'      => [
                        'exclude' => true
                    ],
                    'isException' => [
                        'exclude' => true
                    ]
                ]
            ]
        );

        $this->assertArrayEquals(
            [
                [
                    'id'        => 1,
                    'label'     => 'test_label',
                    '__class__' => Group::class
                ]
            ],
            $result
        );
    }

    public function testSimpleEntityWithMetadataAndExcludeAllPolicy(): void
    {
        $qb = $this->em->getRepository(Group::class)->createQueryBuilder('e')
            ->where('e.id = :id')
            ->setParameter('id', 1);

        $this->setQueryExpectation(
            $this->getDriverConnectionMock($this->em),
            'SELECT g0_.id AS id_0, g0_.label AS label_1'
            . ' FROM group_table g0_'
            . ' WHERE g0_.id = ?',
            [
                [
                    'id_0'    => 1,
                    'label_1' => 'test_label'
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
                    'label'  => null,
                    'entity' => [
                        'property_path' => '__class__'
                    ]
                ]
            ]
        );

        $this->assertArrayEquals(
            [
                [
                    'id'     => 1,
                    'label'  => 'test_label',
                    'entity' => Group::class
                ]
            ],
            $result
        );
    }

    /**
     * tests that not configured relations is skipped
     * it is a temporary fix until the identifier field will not be used by default for them
     */
    public function testNotConfiguredRelations(): void
    {
        $qb = $this->em->getRepository(Product::class)->createQueryBuilder('e')
            ->where('e.id = :id')
            ->setParameter('id', 1);

        $this->setQueryExpectation(
            $this->getDriverConnectionMock($this->em),
            'SELECT p0_.id AS id_0, p0_.name AS name_1'
            . ' FROM product_table p0_'
            . ' WHERE p0_.id = ?',
            [
                [
                    'id_0'   => 1,
                    'name_1' => 'product_name'
                ]
            ],
            [1 => 1],
            [1 => \PDO::PARAM_INT]
        );

        $result = $this->serializer->serialize(
            $qb,
            [
                'fields' => [
                    'id'   => null,
                    'name' => null
                ]
            ]
        );

        $this->assertArrayEquals(
            [
                [
                    'id'   => 1,
                    'name' => 'product_name'
                ]
            ],
            $result
        );
    }

    public function testSimpleEntityWithRenamedFields(): void
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
                'exclusion_policy' => 'all',
                'fields'           => [
                    'id'          => null,
                    'renamedName' => [
                        'property_path' => 'name'
                    ],
                    'category'    => [
                        'exclusion_policy' => 'all',
                        'fields'           => [
                            'renamedLabel' => [
                                'property_path' => 'label'
                            ]
                        ]
                    ],
                    'products'    => [
                        'exclusion_policy' => 'all',
                        'fields'           => [
                            'renamedName' => [
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
                    'id'          => 1,
                    'renamedName' => 'user_name',
                    'category'    => [
                        'renamedLabel' => 'category_label'
                    ],
                    'products'    => [
                        ['renamedName' => 'product_name']
                    ]
                ]
            ],
            $result
        );
    }
}
