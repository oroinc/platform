<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Provider;

use Oro\Bundle\ApiBundle\Provider\EntityTitleProvider;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity;
use Oro\Bundle\ApiBundle\Tests\Unit\OrmRelatedTestCase;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;

class EntityTitleProviderTest extends OrmRelatedTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|EntityNameResolver */
    private $entityNameResolver;

    /** @var EntityTitleProvider */
    private $entityTitleProvider;

    protected function setUp()
    {
        parent::setUp();

        $this->entityNameResolver = $this->createMock(EntityNameResolver::class);

        $this->entityNameResolver->expects(self::any())
            ->method('prepareNameDQL')
            ->willReturnCallback(
                function ($expr, $castToString) {
                    self::assertTrue($castToString);

                    return $expr ?: '\'\'';
                }
            );

        $this->entityTitleProvider = new EntityTitleProvider(
            $this->doctrineHelper,
            $this->entityNameResolver
        );
    }

    public function testGetTitlesForNotManageableEntity()
    {
        $this->notManageableClassNames = [Entity\Product::class];

        $targets = [
            Entity\Product::class => ['id', [1]]
        ];

        self::assertSame(
            [],
            $this->entityTitleProvider->getTitles($targets)
        );
    }

    public function testGetTitlesForEntityWithoutTitleDqlExpr()
    {
        $targets = [
            Entity\Product::class => ['id', [1]]
        ];

        $this->entityNameResolver->expects(self::once())
            ->method('getNameDQL')
            ->with(Entity\Product::class, 'e', null, null)
            ->willReturn(null);

        $this->setQueryExpectation(
            $this->getDriverConnectionMock($this->em),
            'SELECT \'' . Entity\Product::class . '\' AS sclr_0, \'\' AS sclr_1, p0_.id AS id_2'
            . ' FROM product_table p0_'
            . ' WHERE p0_.id IN (1)',
            [
                [
                    'sclr_0' => Entity\Product::class,
                    'sclr_1' => '',
                    'id_2'   => 1
                ]
            ]
        );

        self::assertEquals(
            [
                [
                    'id'     => 1,
                    'entity' => Entity\Product::class,
                    'title'  => ''
                ]
            ],
            $this->entityTitleProvider->getTitles($targets)
        );
    }

    public function testGetTitlesForOneEntityType()
    {
        $targets = [
            Entity\Product::class => ['id', [1]]
        ];

        $this->entityNameResolver->expects(self::once())
            ->method('getNameDQL')
            ->with(Entity\Product::class, 'e', null, null)
            ->willReturn('e.name');

        $this->setQueryExpectation(
            $this->getDriverConnectionMock($this->em),
            'SELECT \'' . Entity\Product::class . '\' AS sclr_0, p0_.name AS name_1, p0_.id AS id_2'
            . ' FROM product_table p0_'
            . ' WHERE p0_.id IN (1)',
            [
                [
                    'sclr_0' => Entity\Product::class,
                    'name_1' => 'title 1',
                    'id_2'   => 1
                ]
            ]
        );

        self::assertEquals(
            [
                [
                    'id'     => 1,
                    'entity' => Entity\Product::class,
                    'title'  => 'title 1'
                ]
            ],
            $this->entityTitleProvider->getTitles($targets)
        );
    }

    public function testGetTitlesWhenTargetsContainBothNotManageableAndManageableEntities()
    {
        $this->notManageableClassNames = [Entity\User::class];

        $targets = [
            Entity\User::class    => ['id', [123]],
            Entity\Product::class => ['id', [456]]
        ];

        $this->entityNameResolver->expects(self::once())
            ->method('getNameDQL')
            ->with(Entity\Product::class, 'e', null, null)
            ->willReturn('e.name');

        $this->setQueryExpectation(
            $this->getDriverConnectionMock($this->em),
            'SELECT \'' . Entity\Product::class . '\' AS sclr_0, p0_.name AS name_1, p0_.id AS id_2'
            . ' FROM product_table p0_'
            . ' WHERE p0_.id IN (456)',
            [
                [
                    'sclr_0' => Entity\Product::class,
                    'name_1' => 'title 1',
                    'id_2'   => 456
                ]
            ]
        );

        self::assertEquals(
            [
                [
                    'id'     => 456,
                    'entity' => Entity\Product::class,
                    'title'  => 'title 1'
                ]
            ],
            $this->entityTitleProvider->getTitles($targets)
        );
    }

    public function testGetTitlesForSeveralEntityTypesWithSameIdentifierType()
    {
        $targets = [
            Entity\Product::class => ['id', [123]],
            Entity\User::class    => ['id', [456]]
        ];

        $this->entityNameResolver->expects(self::exactly(2))
            ->method('getNameDQL')
            ->willReturnMap(
                [
                    [Entity\Product::class, 'e', null, null, 'e.name'],
                    [Entity\User::class, 'e', null, null, 'COALESCE(e.name, \'\')']
                ]
            );

        $this->setQueryExpectation(
            $this->getDriverConnectionMock($this->em),
            'SELECT entity.id_2 AS id, entity.sclr_0 AS entity, entity.name_1 AS title'
            . ' FROM ('
            . '(SELECT \'' . Entity\Product::class . '\' AS sclr_0, p0_.name AS name_1, p0_.id AS id_2'
            . ' FROM product_table p0_'
            . ' WHERE p0_.id IN (123))'
            . ' UNION ALL '
            . '(SELECT \'' . Entity\User::class . '\' AS sclr_0, COALESCE(u0_.name, \'\') AS sclr_1, u0_.id AS id_2'
            . ' FROM user_table u0_'
            . ' WHERE u0_.id IN (456))'
            . ') entity',
            [
                [
                    'id'     => 123,
                    'entity' => Entity\Product::class,
                    'title'  => 'product title 1'
                ],
                [
                    'id'     => 456,
                    'entity' => Entity\User::class,
                    'title'  => 'user title 1'
                ]
            ]
        );

        self::assertEquals(
            [
                [
                    'id'     => 123,
                    'entity' => Entity\Product::class,
                    'title'  => 'product title 1'
                ],
                [
                    'id'     => 456,
                    'entity' => Entity\User::class,
                    'title'  => 'user title 1'
                ]
            ],
            $this->entityTitleProvider->getTitles($targets)
        );
    }

    public function testGetTitlesForSeveralEntityTypesWithDifferentIdentifierType()
    {
        $targets = [
            Entity\Product::class  => ['id', [123]],
            Entity\User::class     => ['id', [456]],
            Entity\Category::class => ['name', ['category1']]
        ];

        $this->entityNameResolver->expects(self::exactly(3))
            ->method('getNameDQL')
            ->willReturnMap(
                [
                    [Entity\Product::class, 'e', null, null, 'e.name'],
                    [Entity\User::class, 'e', null, null, 'COALESCE(e.name, \'\')'],
                    [Entity\Category::class, 'e', null, null, 'e.label']
                ]
            );

        $conn = $this->getDriverConnectionMock($this->em);
        $this->setQueryExpectationAt(
            $conn,
            0,
            'SELECT entity.id_2 AS id, entity.sclr_0 AS entity, entity.name_1 AS title'
            . ' FROM ('
            . '(SELECT \'' . Entity\Product::class . '\' AS sclr_0, p0_.name AS name_1, p0_.id AS id_2'
            . ' FROM product_table p0_'
            . ' WHERE p0_.id IN (123))'
            . ' UNION ALL '
            . '(SELECT \'' . Entity\User::class . '\' AS sclr_0, COALESCE(u0_.name, \'\') AS sclr_1, u0_.id AS id_2'
            . ' FROM user_table u0_'
            . ' WHERE u0_.id IN (456))'
            . ') entity',
            [
                [
                    'id'     => 123,
                    'entity' => Entity\Product::class,
                    'title'  => 'product title 1'
                ],
                [
                    'id'     => 456,
                    'entity' => Entity\User::class,
                    'title'  => 'user title 1'
                ]
            ]
        );
        $this->setQueryExpectationAt(
            $conn,
            1,
            'SELECT \'' . Entity\Category::class . '\' AS sclr_0, c0_.label AS label_1, c0_.name AS name_2'
            . ' FROM category_table c0_'
            . ' WHERE c0_.name IN (\'category1\')',
            [
                [
                    'sclr_0'  => Entity\Category::class,
                    'label_1' => 'category title 1',
                    'name_2'  => 'category1'
                ]
            ]
        );

        self::assertEquals(
            [
                [
                    'id'     => 123,
                    'entity' => Entity\Product::class,
                    'title'  => 'product title 1'
                ],
                [
                    'id'     => 456,
                    'entity' => Entity\User::class,
                    'title'  => 'user title 1'
                ],
                [
                    'id'     => 'category1',
                    'entity' => Entity\Category::class,
                    'title'  => 'category title 1'
                ]
            ],
            $this->entityTitleProvider->getTitles($targets)
        );
    }

    public function testGetTitlesForOneEntityTypeWithCompositeIdentifier()
    {
        $targets = [
            Entity\CompositeKeyEntity::class => [['id', 'title'], [[1, 'item 1']]]
        ];

        $this->entityNameResolver->expects(self::once())
            ->method('getNameDQL')
            ->with(Entity\CompositeKeyEntity::class, 'e', null, null)
            ->willReturn('e.title');

        $this->setQueryExpectation(
            $this->getDriverConnectionMock($this->em),
            'SELECT \'' . Entity\CompositeKeyEntity::class . '\' AS sclr_0, c0_.title AS title_1'
            . ', c0_.id AS id_2, c0_.title AS title_3'
            . ' FROM composite_key_entity c0_'
            . ' WHERE c0_.id = 1 AND c0_.title = \'item 1\'',
            [
                [
                    'sclr_0'  => Entity\CompositeKeyEntity::class,
                    'title_1' => 'item 1',
                    'id_2'    => 1,
                    'title_3' => 'item 1'
                ]
            ]
        );

        self::assertEquals(
            [
                [
                    'id'     => ['id' => 1, 'title' => 'item 1'],
                    'entity' => Entity\CompositeKeyEntity::class,
                    'title'  => 'item 1'
                ]
            ],
            $this->entityTitleProvider->getTitles($targets)
        );
    }
}
