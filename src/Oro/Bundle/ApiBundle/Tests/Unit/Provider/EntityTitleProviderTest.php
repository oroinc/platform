<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Provider;

use Oro\Bundle\ApiBundle\Provider\EntityTitleProvider;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity;
use Oro\Bundle\ApiBundle\Tests\Unit\OrmRelatedTestCase;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;

class EntityTitleProviderTest extends OrmRelatedTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $entityNameResolver;

    /** @var EntityTitleProvider */
    protected $entityTitleProvider;

    protected function setUp()
    {
        parent::setUp();

        $this->entityNameResolver = $this->getMockBuilder(EntityNameResolver::class)
            ->disableOriginalConstructor()
            ->getMock();

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
            Entity\Product::class => [1]
        ];

        self::assertSame(
            [],
            $this->entityTitleProvider->getTitles($targets)
        );
    }

    public function testGetTitlesForEntityWithoutTitleDqlExpr()
    {
        $targets = [
            Entity\Product::class => [1]
        ];

        $this->entityNameResolver->expects(self::once())
            ->method('getNameDQL')
            ->with(Entity\Product::class, 'e', null, null)
            ->willReturn(null);

        $this->setQueryExpectation(
            $this->getDriverConnectionMock($this->em),
            'SELECT p0_.id AS id_0, \'' . Entity\Product::class . '\' AS sclr_1, \'\' AS sclr_2'
            . ' FROM product_table p0_'
            . ' WHERE p0_.id IN (1)',
            [
                [
                    'id_0'   => 1,
                    'sclr_1' => Entity\Product::class,
                    'sclr_2' => ''
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
            Entity\Product::class => [1]
        ];

        $this->entityNameResolver->expects(self::once())
            ->method('getNameDQL')
            ->with(Entity\Product::class, 'e', null, null)
            ->willReturn('e.name');

        $this->setQueryExpectation(
            $this->getDriverConnectionMock($this->em),
            'SELECT p0_.id AS id_0, \'' . Entity\Product::class . '\' AS sclr_1, p0_.name AS name_2'
            . ' FROM product_table p0_'
            . ' WHERE p0_.id IN (1)',
            [
                [
                    'id_0'   => 1,
                    'sclr_1' => Entity\Product::class,
                    'name_2' => 'title 1'
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
            Entity\User::class    => [123],
            Entity\Product::class => [456]
        ];

        $this->entityNameResolver->expects(self::once())
            ->method('getNameDQL')
            ->with(Entity\Product::class, 'e', null, null)
            ->willReturn('e.name');

        $this->setQueryExpectation(
            $this->getDriverConnectionMock($this->em),
            'SELECT p0_.id AS id_0, \'' . Entity\Product::class . '\' AS sclr_1, p0_.name AS name_2'
            . ' FROM product_table p0_'
            . ' WHERE p0_.id IN (456)',
            [
                [
                    'id_0'   => 456,
                    'sclr_1' => Entity\Product::class,
                    'name_2' => 'title 1'
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
            Entity\Product::class => [123],
            Entity\User::class    => [456],
        ];

        $this->entityNameResolver->expects(self::exactly(2))
            ->method('getNameDQL')
            ->willReturnMap(
                [
                    [Entity\Product::class, 'e', null, null, 'e.name'],
                    [Entity\User::class, 'e', null, null, 'COALESCE(e.name, \'\')'],
                ]
            );

        $this->setQueryExpectation(
            $this->getDriverConnectionMock($this->em),
            'SELECT entity.id_0 AS id, entity.sclr_1 AS entity, entity.name_2 AS title'
            . ' FROM ('
            . '(SELECT p0_.id AS id_0, \'' . Entity\Product::class . '\' AS sclr_1, p0_.name AS name_2'
            . ' FROM product_table p0_'
            . ' WHERE p0_.id IN (123))'
            . ' UNION ALL '
            . '(SELECT u0_.id AS id_0, \'' . Entity\User::class . '\' AS sclr_1, COALESCE(u0_.name, \'\') AS sclr_2'
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
                ],
            ],
            $this->entityTitleProvider->getTitles($targets)
        );
    }

    public function testGetTitlesForSeveralEntityTypesWithDifferentIdentifierType()
    {
        $targets = [
            Entity\Product::class  => [123],
            Entity\User::class     => [456],
            Entity\Category::class => ['category1'],
        ];

        $this->entityNameResolver->expects(self::exactly(3))
            ->method('getNameDQL')
            ->willReturnMap(
                [
                    [Entity\Product::class, 'e', null, null, 'e.name'],
                    [Entity\User::class, 'e', null, null, 'COALESCE(e.name, \'\')'],
                    [Entity\Category::class, 'e', null, null, 'e.label'],
                ]
            );

        $conn = $this->getDriverConnectionMock($this->em);
        $this->setQueryExpectationAt(
            $conn,
            0,
            'SELECT entity.id_0 AS id, entity.sclr_1 AS entity, entity.name_2 AS title'
            . ' FROM ('
            . '(SELECT p0_.id AS id_0, \'' . Entity\Product::class . '\' AS sclr_1, p0_.name AS name_2'
            . ' FROM product_table p0_'
            . ' WHERE p0_.id IN (123))'
            . ' UNION ALL '
            . '(SELECT u0_.id AS id_0, \'' . Entity\User::class . '\' AS sclr_1, COALESCE(u0_.name, \'\') AS sclr_2'
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
            'SELECT c0_.name AS name_0, \'' . Entity\Category::class . '\' AS sclr_1, c0_.label AS label_2'
            . ' FROM category_table c0_'
            . ' WHERE c0_.name IN (\'category1\')',
            [
                [
                    'name_0'  => 'category1',
                    'sclr_1'  => Entity\Category::class,
                    'label_2' => 'category title 1'
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
                ],
            ],
            $this->entityTitleProvider->getTitles($targets)
        );
    }
}
