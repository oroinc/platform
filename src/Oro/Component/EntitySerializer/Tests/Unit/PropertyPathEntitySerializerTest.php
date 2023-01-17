<?php

namespace Oro\Component\EntitySerializer\Tests\Unit;

use Doctrine\ORM\Query;
use Oro\Component\EntitySerializer\Tests\Unit\Fixtures\Entity\Group;
use Oro\Component\EntitySerializer\Tests\Unit\Fixtures\Entity\Product;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class PropertyPathEntitySerializerTest extends EntitySerializerTestCase
{
    public function testRename(): void
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
                    'name_1' => 'group_name'
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
                    ]
                ]
            ]
        );

        $this->assertArrayEquals(
            [
                [
                    'id'      => 1,
                    'newName' => 'group_name'
                ]
            ],
            $result
        );
    }

    public function testRenameChild(): void
    {
        $qb = $this->em->getRepository(Product::class)->createQueryBuilder('e')
            ->where('e.id = :id')
            ->setParameter('id', 1);

        $this->setQueryExpectation(
            $this->getDriverConnectionMock($this->em),
            'SELECT p0_.id AS id_0,'
            . ' u1_.id AS id_1, u1_.name AS name_2'
            . ' FROM product_table p0_'
            . ' LEFT JOIN user_table u1_ ON p0_.owner_id = u1_.id'
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
                'exclusion_policy' => 'all',
                'fields'           => [
                    'owner' => [
                        'exclusion_policy' => 'all',
                        'fields'           => [
                            'newName' => [
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
                    'owner' => [
                        'newName' => 'user_name'
                    ]
                ]
            ],
            $result
        );
    }

    public function testMetadata(): void
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
                    'id'     => null,
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
                    'entity' => Group::class
                ]
            ],
            $result
        );
    }

    public function testPropertyPath(): void
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
            . ' LEFT JOIN user_table u1_ ON p0_.owner_id = u1_.id'
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
                'exclusion_policy' => 'all',
                'fields'           => [
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

    public function testPropertyPathWithExplicitlyDefinedAssociations(): void
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
            . ' LEFT JOIN user_table u1_ ON p0_.owner_id = u1_.id'
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
                'exclusion_policy' => 'all',
                'fields'           => [
                    'ownerName'     => [
                        'property_path' => 'owner.name'
                    ],
                    'ownerCategory' => [
                        'property_path' => 'owner.category.label'
                    ],
                    'owner'         => [
                        'id'     => null,
                        'name'   => null,
                        'fields' => [
                            'category' => [
                                'name'  => null,
                                'label' => null
                            ]
                        ]
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

    public function testPropertyPathWithExcludedFirstLevelAssociation(): void
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
            . ' LEFT JOIN user_table u1_ ON p0_.owner_id = u1_.id'
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
                'exclusion_policy' => 'all',
                'fields'           => [
                    'ownerName'     => [
                        'property_path' => 'owner.name'
                    ],
                    'ownerCategory' => [
                        'property_path' => 'owner.category.label'
                    ],
                    'owner'         => [
                        'exclude' => true
                    ]
                ]
            ]
        );

        $this->assertArrayEquals(
            [
                [
                    'ownerName'     => 'user_name',
                    'ownerCategory' => 'user_category_label'
                ]
            ],
            $result
        );
    }

    public function testPropertyPathWithExcludedFirstLevelAssociationAndDisabledPartialLoad(): void
    {
        $qb = $this->em->getRepository(Product::class)->createQueryBuilder('e')
            ->where('e.id = :id')
            ->setParameter('id', 1);

        $this->addQueryExpectation(
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
                'disable_partial_load' => true,
                'exclusion_policy'     => 'all',
                'fields'               => [
                    'id'            => null,
                    'name'          => null,
                    'owner'         => [
                        'exclude' => true
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
                    'id'            => 1,
                    'name'          => 'product_name',
                    'ownerCategory' => 'user_category_label'
                ]
            ],
            $result
        );
    }

    public function testPropertyPathWithExcludedFirstAndSecondLevelAssociations(): void
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
            . ' LEFT JOIN user_table u1_ ON p0_.owner_id = u1_.id'
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
                'exclusion_policy' => 'all',
                'fields'           => [
                    'ownerName'     => [
                        'property_path' => 'owner.name'
                    ],
                    'ownerCategory' => [
                        'property_path' => 'owner.category.label'
                    ],
                    'owner'         => [
                        'exclude' => true,
                        'fields'  => [
                            'category' => [
                                'exclude' => true
                            ]
                        ]
                    ]
                ]
            ]
        );

        $this->assertArrayEquals(
            [
                [
                    'ownerName'     => 'user_name',
                    'ownerCategory' => 'user_category_label'
                ]
            ],
            $result
        );
    }

    public function testPropertyPathWithExcludedFirstAndSecondLevelAssociationsAndDisabledPartialLoad(): void
    {
        $qb = $this->em->getRepository(Product::class)->createQueryBuilder('e')
            ->where('e.id = :id')
            ->setParameter('id', 1);

        $this->addQueryExpectation(
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
                'disable_partial_load' => true,
                'exclusion_policy'     => 'all',
                'fields'               => [
                    'id'            => null,
                    'name'          => null,
                    'owner'         => [
                        'exclude' => true,
                        'fields'  => [
                            'category' => [
                                'exclude' => true
                            ]
                        ]
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
                    'id'            => 1,
                    'name'          => 'product_name',
                    'ownerCategory' => 'user_category_label'
                ]
            ],
            $result
        );
    }

    public function testPropertyPathWithDisabledForcePartialLoadQueryHint(): void
    {
        $qb = $this->em->getRepository(Product::class)->createQueryBuilder('e')
            ->where('e.id = :id')
            ->setParameter('id', 1);

        $this->addQueryExpectation(
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
                'hints'            => [
                    ['name' => Query::HINT_FORCE_PARTIAL_LOAD, 'value' => false]
                ],
                'exclusion_policy' => 'all',
                'fields'           => [
                    'id'            => null,
                    'name'          => null,
                    'owner'         => [
                        'exclude' => true
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
                    'id'            => 1,
                    'name'          => 'product_name',
                    'ownerCategory' => 'user_category_label'
                ]
            ],
            $result
        );
    }

    public function testPropertyPathWithDisabledPartialLoad(): void
    {
        $qb = $this->em->getRepository(Product::class)->createQueryBuilder('e')
            ->where('e.id = :id')
            ->setParameter('id', 1);

        $this->addQueryExpectation(
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
                'disable_partial_load' => true,
                'exclusion_policy'     => 'all',
                'fields'               => [
                    'id'            => null,
                    'name'          => null,
                    'ownerCategory' => [
                        'property_path' => 'owner.category.label'
                    ]
                ]
            ]
        );

        $this->assertArrayEquals(
            [
                [
                    'id'            => 1,
                    'name'          => 'product_name',
                    'owner'         => [
                        'id'       => 10,
                        'name'     => 'user_name',
                        'category' => [
                            'name'  => 'user_category_name',
                            'label' => 'user_category_label'
                        ]
                    ],
                    'ownerCategory' => 'user_category_label'
                ]
            ],
            $result
        );
    }

    public function testPropertyPathWithDisabledPartialLoadForAllAssociations(): void
    {
        $qb = $this->em->getRepository(Product::class)->createQueryBuilder('e')
            ->where('e.id = :id')
            ->setParameter('id', 1);

        $this->addQueryExpectation(
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
                'disable_partial_load' => true,
                'exclusion_policy'     => 'all',
                'fields'               => [
                    'id'            => null,
                    'name'          => null,
                    'owner'         => [
                        'disable_partial_load' => true,
                        'exclude'              => true,
                        'fields'               => [
                            'category' => [
                                'disable_partial_load' => true,
                                'exclude'              => true
                            ]
                        ]
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
                    'id'            => 1,
                    'name'          => 'product_name',
                    'ownerCategory' => 'user_category_label'
                ]
            ],
            $result
        );
    }

    public function testPropertyPathWithExcludedAndExplicitlyDefinedFirstLevelAssociation(): void
    {
        $qb = $this->em->getRepository(Product::class)->createQueryBuilder('e')
            ->where('e.id = :id')
            ->setParameter('id', 1);

        $this->setQueryExpectation(
            $this->getDriverConnectionMock($this->em),
            'SELECT p0_.id AS id_0, p0_.name AS name_1,'
            . ' u1_.id AS id_2, u1_.name AS name_3,'
            . ' c2_.name AS name_4, c2_.label AS label_5'
            . ' FROM product_table p0_'
            . ' LEFT JOIN user_table u1_ ON p0_.owner_id = u1_.id'
            . ' LEFT JOIN category_table c2_ ON p0_.category_name = c2_.name'
            . ' WHERE p0_.id = ?',
            [
                [
                    'id_0'    => 1,
                    'name_1'  => 'product_name',
                    'id_2'    => 10,
                    'name_3'  => 'user_name',
                    'name_4'  => 'category_name',
                    'label_5' => 'category_label'
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
                ]
            ]
        );

        $this->assertArrayEquals(
            [
                [
                    'id'        => 1,
                    'name'      => 'product_name',
                    'ownerName' => 'user_name',
                    'category'  => [
                        'name' => 'category_label'
                    ]
                ]
            ],
            $result
        );
    }

    public function testPropertyPathForThirdLevelRelation(): void
    {
        $qb = $this->em->getRepository(Product::class)->createQueryBuilder('e')
            ->where('e.id = :id')
            ->setParameter('id', 1);

        $this->addQueryExpectation(
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
                    'category_name_8' => 'user_category_name'
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
                ]
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
                    ]
                ]
            ],
            $result
        );
    }

    public function testPropertyPathForComputedField(): void
    {
        $qb = $this->em->getRepository(Product::class)->createQueryBuilder('e')
            ->where('e.id = :id')
            ->setParameter('id', 1);

        $this->setQueryExpectation(
            $this->getDriverConnectionMock($this->em),
            'SELECT p0_.id AS id_0,'
            . ' u1_.id AS id_1, u1_.name AS name_2'
            . ' FROM product_table p0_'
            . ' LEFT JOIN user_table u1_ ON p0_.owner_id = u1_.id'
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
                'exclusion_policy' => 'all',
                'fields'           => [
                    'ownerName' => [
                        'property_path' => 'owner.computedName.value'
                    ],
                    'owner'     => [
                        'exclude'        => true,
                        'fields'         => [
                            'name' => null
                        ],
                        'post_serialize' => function ($item) {
                            $item['computedName'] = ['value' => $item['name'] . ' (computed)'];

                            return $item;
                        }
                    ]
                ]
            ]
        );

        $this->assertArrayEquals(
            [
                [
                    'ownerName' => 'user_name (computed)'
                ]
            ],
            $result
        );
    }

    public function testPropertyPathForRenamedComputedField(): void
    {
        $qb = $this->em->getRepository(Product::class)->createQueryBuilder('e')
            ->where('e.id = :id')
            ->setParameter('id', 1);

        $this->setQueryExpectation(
            $this->getDriverConnectionMock($this->em),
            'SELECT p0_.id AS id_0,'
            . ' u1_.id AS id_1, u1_.name AS name_2'
            . ' FROM product_table p0_'
            . ' LEFT JOIN user_table u1_ ON p0_.owner_id = u1_.id'
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
                'exclusion_policy' => 'all',
                'fields'           => [
                    'ownerName'    => [
                        'property_path' => 'owner.computedName.value'
                    ],
                    'renamedOwner' => [
                        'property_path'  => 'owner',
                        'exclude'        => true,
                        'fields'         => [
                            'name'                => null,
                            'renamedComputedName' => [
                                'property_path' => 'computedName',
                                'fields'        => [
                                    'renamedValue' => [
                                        'property_path' => 'value'
                                    ]
                                ]
                            ]
                        ],
                        'post_serialize' => function ($item) {
                            $item['renamedComputedName'] = ['renamedValue' => $item['name'] . ' (computed)'];

                            return $item;
                        }
                    ]
                ]
            ]
        );

        $this->assertArrayEquals(
            [
                [
                    'ownerName' => 'user_name (computed)'
                ]
            ],
            $result
        );
    }
}
