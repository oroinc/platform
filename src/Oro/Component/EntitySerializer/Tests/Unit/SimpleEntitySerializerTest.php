<?php

namespace Oro\Component\EntitySerializer\Tests\Unit;

use Oro\Component\EntitySerializer\Tests\Unit\Fixtures\Entity;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class SimpleEntitySerializerTest extends EntitySerializerTestCase
{
    public function testReuseExistingJoin()
    {
        $qb = $this->em->getRepository('Test:Product')->createQueryBuilder('e')
            ->leftJoin('e.owner', 'user')
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
                    'category_name_5' => 'owner_category_name',
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
                        'fields' => 'id',
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

    public function testReuseExistingInnerJoin()
    {
        $qb = $this->em->getRepository('Test:Product')->createQueryBuilder('e')
            ->innerJoin('e.owner', 'user')
            ->where('e.id = :id')
            ->setParameter('id', 1);

        $this->setQueryExpectation(
            $this->getDriverConnectionMock($this->em),
            'SELECT p0_.id AS id_0, p0_.name AS name_1,'
            . ' u1_.id AS id_2,'
            . ' p0_.category_name AS category_name_3, p0_.owner_id AS owner_id_4,'
            . ' u1_.category_name AS category_name_5'
            . ' FROM product_table p0_'
            . ' INNER JOIN user_table u1_ ON p0_.owner_id = u1_.id'
            . ' WHERE p0_.id = ?',
            [
                [
                    'id_0'            => 1,
                    'name_1'          => 'product_name',
                    'id_2'            => 10,
                    'category_name_3' => 'category_name',
                    'owner_id_4'      => 10,
                    'category_name_5' => 'owner_category_name',
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
                        'fields' => 'id',
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

    public function testReuseExistingJoinWithCondition()
    {
        $qb = $this->em->getRepository('Test:Product')->createQueryBuilder('e')
            ->leftJoin('e.owner', 'user', 'WITH', 'e.owner = user.id AND user.name LIKE \'a%\'')
            ->where('e.id = :id')
            ->setParameter('id', 1);

        $this->setQueryExpectation(
            $this->getDriverConnectionMock($this->em),
            'SELECT p0_.id AS id_0, p0_.name AS name_1,'
            . ' u1_.id AS id_2,'
            . ' p0_.category_name AS category_name_3, p0_.owner_id AS owner_id_4,'
            . ' u1_.category_name AS category_name_5'
            . ' FROM product_table p0_'
            . ' LEFT JOIN user_table u1_ ON p0_.owner_id = u1_.id AND (p0_.owner_id = u1_.id AND u1_.name LIKE \'a%\')'
            . ' WHERE p0_.id = ?',
            [
                [
                    'id_0'            => 1,
                    'name_1'          => 'product_name',
                    'id_2'            => 10,
                    'category_name_3' => 'category_name',
                    'owner_id_4'      => 10,
                    'category_name_5' => 'owner_category_name',
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
                        'fields' => 'id',
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

    public function testSimpleEntityWithoutConfig()
    {
        $qb = $this->em->getRepository('Test:Group')->createQueryBuilder('e')
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

    public function testSimpleEntityWithExclusion()
    {
        $qb = $this->em->getRepository('Test:Group')->createQueryBuilder('e')
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
                    'public_2' => 1,
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
                    'isException' => ['exclude' => true],
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

    public function testSimpleEntityWithComputedField()
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
                    'name_1' => 'test_name',
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
                    'computedName' => null,
                ]
            ]
        );

        $this->assertArrayEquals(
            [
                [
                    'id'           => 1,
                    'name'         => 'test_name',
                    'computedName' => 'test_name (COMPUTED)',
                ]
            ],
            $result
        );
    }

    public function testSimpleEntityWithExclusionAndPartialLoadDisabled()
    {
        $qb = $this->em->getRepository('Test:Group')->createQueryBuilder('e')
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

    public function testSimpleEntityWithSpecifiedFieldsButNoExclusionPolicy()
    {
        $qb = $this->em->getRepository('Test:Group')->createQueryBuilder('e')
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
                    'name' => null,
                ],
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

    public function testSimpleEntityWithSpecifiedFieldsOnly()
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
                    'name_1' => 'test_name',
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
                    'name' => null,
                ],
            ]
        );

        $this->assertArrayEquals(
            [
                [
                    'id'   => 1,
                    'name' => 'test_name',
                ]
            ],
            $result
        );
    }

    public function testSimpleEntityWithSpecifiedFieldsAndExclusions()
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
                    'id_0' => 1,
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
                    ],
                ],
            ]
        );

        $this->assertArrayEquals(
            [
                [
                    'id' => 1,
                ]
            ],
            $result
        );
    }

    public function testSimpleEntityWithMetadata()
    {
        $qb = $this->em->getRepository('Test:Group')->createQueryBuilder('e')
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
                    'entity' => Entity\Group::class
                ]
            ],
            $result
        );
    }

    public function testSimpleEntityWithMetadataWithoutPropertyPath()
    {
        $qb = $this->em->getRepository('Test:Group')->createQueryBuilder('e')
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
                    '__class__' => Entity\Group::class
                ]
            ],
            $result
        );
    }

    public function testSimpleEntityWithMetadataAndExcludeAllPolicy()
    {
        $qb = $this->em->getRepository('Test:Group')->createQueryBuilder('e')
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
                    'entity' => Entity\Group::class
                ]
            ],
            $result
        );
    }

    /**
     * tests that not configured relations is skipped
     * it is a temporary fix until the identifier field will not be used by default for them
     */
    public function testNotConfiguredRelations()
    {
        $qb = $this->em->getRepository('Test:Product')->createQueryBuilder('e')
            ->where('e.id = :id')
            ->setParameter('id', 1);

        $this->setQueryExpectation(
            $this->getDriverConnectionMock($this->em),
            'SELECT p0_.id AS id_0, p0_.name AS name_1,'
            . ' p0_.category_name AS category_name_2, p0_.owner_id AS owner_id_3'
            . ' FROM product_table p0_'
            . ' WHERE p0_.id = ?',
            [
                [
                    'id_0'            => 1,
                    'name_1'          => 'product_name',
                    'category_name_2' => 'category_name',
                    'owner_id_3'      => 10,
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

    public function testSimpleEntityWithRenamedFields()
    {
        $qb = $this->em->getRepository('Test:User')->createQueryBuilder('e')
            ->where('e.id = :id')
            ->setParameter('id', 1);

        $conn = $this->getDriverConnectionMock($this->em);

        $this->setQueryExpectationAt(
            $conn,
            0,
            'SELECT u0_.id AS id_0, u0_.name AS name_1,'
            . ' c1_.name AS name_2, c1_.label AS label_3,'
            . ' u0_.category_name AS category_name_4'
            . ' FROM user_table u0_'
            . ' LEFT JOIN category_table c1_ ON u0_.category_name = c1_.name'
            . ' WHERE u0_.id = ?',
            [
                [
                    'id_0'            => 1,
                    'name_1'          => 'user_name',
                    'name_2'          => 'category_name',
                    'label_3'         => 'category_label',
                    'category_name_4' => 'category_name'
                ]
            ],
            [1 => 1],
            [1 => \PDO::PARAM_INT]
        );

        $this->setQueryExpectationAt(
            $conn,
            1,
            'SELECT u0_.id AS id_0, p1_.name AS name_1, p1_.id AS id_2'
            . ' FROM product_table p1_'
            . ' INNER JOIN user_table u0_ ON (p1_.owner_id = u0_.id)'
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
                        ],
                    ],
                    'products'    => [
                        'exclusion_policy' => 'all',
                        'fields'           => [
                            'renamedName' => [
                                'property_path' => 'name'
                            ]
                        ],
                    ],
                ]
            ]
        );

        $this->assertArrayEquals(
            [
                [
                    'id'              => 1,
                    'renamedName'     => 'user_name',
                    'category'        => [
                        'renamedLabel' => 'category_label'
                    ],
                    'products' => [
                        ['renamedName' => 'product_name']
                    ]
                ]
            ],
            $result
        );
    }
}
