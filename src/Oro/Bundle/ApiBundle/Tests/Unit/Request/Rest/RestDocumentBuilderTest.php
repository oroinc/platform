<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Request\Rest;

use Oro\Bundle\ApiBundle\Metadata\ExternalLinkMetadata;
use Oro\Bundle\ApiBundle\Metadata\MetaAttributeMetadata;
use Oro\Bundle\ApiBundle\Metadata\MetaPropertyMetadata;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorMetaProperty;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\Rest\RestDocumentBuilder;
use Oro\Bundle\ApiBundle\Tests\Unit\Request\DocumentBuilderTestCase;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Psr\Log\LoggerInterface;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class RestDocumentBuilderTest extends DocumentBuilderTestCase
{
    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var RestDocumentBuilder */
    private $documentBuilder;

    #[\Override]
    protected function setUp(): void
    {
        $this->requestType = new RequestType([RequestType::REST]);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->documentBuilder = new RestDocumentBuilder(
            $this->getValueNormalizer(),
            $this->getEntityIdTransformerRegistry($this->getEntityIdTransformer()),
            $this->logger
        );
    }

    public function testSetDataObjectWithoutMetadata()
    {
        $object = [
            'id' => 123,
            'name' => 'Name'
        ];

        $this->documentBuilder->setDataObject($object, $this->requestType, null);
        self::assertEquals(
            [
                'id' => 123,
                'name' => 'Name'
            ],
            $this->documentBuilder->getDocument()
        );
    }

    public function testSetDataObjectWithoutMetadataAndWithObjectType()
    {
        $object = [
            'id' => 123,
            'name' => 'Name',
            '__class__' => 'Test\Class'
        ];

        $this->documentBuilder->setDataObject($object, $this->requestType, null);
        self::assertEquals(
            [
                'id' => 123,
                'name' => 'Name',
                'entity' => 'Test\Class'
            ],
            $this->documentBuilder->getDocument()
        );
    }

    public function testSetDataCollectionWithoutMetadata()
    {
        $object = [
            'id' => 123,
            'name' => 'Name'
        ];

        $this->documentBuilder->setDataCollection([$object], $this->requestType, null);
        self::assertEquals(
            [
                [
                    'id' => 123,
                    'name' => 'Name'
                ]
            ],
            $this->documentBuilder->getDocument()
        );
    }

    public function testSetDataCollectionWithoutMetadataAndWithObjectType()
    {
        $object = [
            'id' => 123,
            'name' => 'Name',
            '__class__' => 'Test\Class'
        ];

        $this->documentBuilder->setDataCollection([$object], $this->requestType, null);
        self::assertEquals(
            [
                [
                    'id' => 123,
                    'name' => 'Name',
                    'entity' => 'Test\Class'
                ]
            ],
            $this->documentBuilder->getDocument()
        );
    }

    public function testSetDataCollectionOfScalarsWithoutMetadata()
    {
        $this->documentBuilder->setDataCollection(['val1', null, 'val3'], $this->requestType, null);
        self::assertEquals(
            ['val1', null, 'val3'],
            $this->documentBuilder->getDocument()
        );
    }

    public function testSetDataObjectWithMetadata()
    {
        $object = [
            'id' => 123,
            'name' => 'Name',
            'meta1' => 'Meta1',
            'category' => 456,
            'group' => null,
            'role' => ['id' => 789],
            'categories' => [
                ['id' => 456],
                ['id' => 457]
            ],
            'groups' => null,
            'products' => [],
            'roles' => [
                ['id' => 789, 'name' => 'Role1'],
                ['id' => 780, 'name' => 'Role2']
            ]
        ];

        $metadata = $this->getEntityMetadata('Test\Entity', ['id']);
        $metadata->addField($this->createFieldMetadata('id'));
        $metadata->addField($this->createFieldMetadata('name'));
        $metadata->addMetaProperty($this->createMetaPropertyMetadata('meta1'));
        $metadata->addAssociation($this->createAssociationMetadata('category', 'Test\Category'));
        $metadata->addAssociation($this->createAssociationMetadata('group', 'Test\Groups'));
        $metadata->addAssociation($this->createAssociationMetadata('role', 'Test\Role'));
        $metadata->addAssociation($this->createAssociationMetadata('categories', 'Test\Category', true));
        $metadata->addAssociation($this->createAssociationMetadata('groups', 'Test\Group', true));
        $metadata->addAssociation($this->createAssociationMetadata('products', 'Test\Product', true));
        $metadata->addAssociation($this->createAssociationMetadata('roles', 'Test\Role', true));
        $metadata->getAssociation('roles')->getTargetMetadata()->addField($this->createFieldMetadata('name'));

        $this->documentBuilder->setDataObject($object, $this->requestType, $metadata);
        self::assertEquals(
            [
                'id' => 'Test\Entity::123',
                'name' => 'Name',
                'meta1' => 'Meta1',
                'category' => 'Test\Category::456',
                'group' => null,
                'role' => 789,
                'categories' => [456, 457],
                'groups' => [],
                'products' => [],
                'roles' => [
                    ['id' => 'Test\Role::789', 'name' => 'Role1'],
                    ['id' => 'Test\Role::780', 'name' => 'Role2']
                ]
            ],
            $this->documentBuilder->getDocument()
        );
    }

    public function testSetDataCollectionWithMetadata()
    {
        $object = [
            'id' => 123,
            'name' => 'Name',
            'meta1' => 'Meta1',
            'category' => 456,
            'group' => null,
            'role' => 789,
            'categories' => [
                ['id' => 456],
                ['id' => 457]
            ],
            'groups' => [],
            'products' => [],
            'roles' => [
                ['id' => 789, 'name' => 'Role1'],
                ['id' => 780, 'name' => 'Role2']
            ]
        ];

        $metadata = $this->getEntityMetadata('Test\Entity', ['id']);
        $metadata->addField($this->createFieldMetadata('id'));
        $metadata->addField($this->createFieldMetadata('name'));
        $metadata->addMetaProperty($this->createMetaPropertyMetadata('meta1'));
        $metadata->addAssociation($this->createAssociationMetadata('category', 'Test\Category'));
        $metadata->addAssociation($this->createAssociationMetadata('group', 'Test\Groups'));
        $metadata->addAssociation($this->createAssociationMetadata('role', 'Test\Role'));
        $metadata->addAssociation($this->createAssociationMetadata('categories', 'Test\Category', true));
        $metadata->addAssociation($this->createAssociationMetadata('groups', 'Test\Group', true));
        $metadata->addAssociation($this->createAssociationMetadata('products', 'Test\Product', true));
        $metadata->addAssociation($this->createAssociationMetadata('roles', 'Test\Role', true));
        $metadata->getAssociation('roles')->getTargetMetadata()->addField($this->createFieldMetadata('name'));

        $this->documentBuilder->setDataCollection([$object], $this->requestType, $metadata);
        self::assertEquals(
            [
                [
                    'id' => 'Test\Entity::123',
                    'name' => 'Name',
                    'meta1' => 'Meta1',
                    'category' => 'Test\Category::456',
                    'group' => null,
                    'role' => 'Test\Role::789',
                    'categories' => [456, 457],
                    'groups' => [],
                    'products' => [],
                    'roles' => [
                        ['id' => 'Test\Role::789', 'name' => 'Role1'],
                        ['id' => 'Test\Role::780', 'name' => 'Role2']
                    ]
                ]
            ],
            $this->documentBuilder->getDocument()
        );
    }

    public function testSetDataObjectWithLinks()
    {
        $object = [
            'code' => 123,
            'name' => 'Name'
        ];

        $metadata = $this->getEntityMetadata('Test\Entity', ['code']);
        $metadata->addField($this->createFieldMetadata('code'));
        $metadata->addField($this->createFieldMetadata('name'));
        $metadata->addLink(
            'self',
            new ExternalLinkMetadata('/api/{__type__}/{id}', ['__type__' => null, 'id' => '__id__'])
        );
        $linkWithMeta = $metadata->addLink(
            'with_meta',
            new ExternalLinkMetadata('/api/{type}/{id}/meta', ['type' => '__type__', 'id' => '__id__'])
        );
        $linkWithMeta->addMetaProperty(new MetaAttributeMetadata('meta1', 'string', 'name'));
        $linkWithMeta->addMetaProperty(new MetaAttributeMetadata('meta2', 'string'));
        $linkWithMeta->addMetaProperty(new MetaAttributeMetadata('meta3', 'string', '__class__'));
        $metadata->addLink(
            'unresolved',
            new ExternalLinkMetadata('/api/{unknown}', ['unknown' => null])
        );

        $this->logger->expects(self::once())
            ->method('notice')
            ->with('Cannot build URL for a link. Missing Parameters: unknown.');

        $this->documentBuilder->setDataObject($object, $this->requestType, $metadata);
        self::assertEquals(
            [
                'code' => 'Test\Entity::123',
                'name' => 'Name',
                'links' => [
                    'self' => '/api/test_entity/Test\Entity::123',
                    'with_meta' => [
                        'href' => '/api/test_entity/Test\Entity::123/meta',
                        'meta1' => 'Name',
                        'meta3' => 'Test\Entity'
                    ]
                ]
            ],
            $this->documentBuilder->getDocument()
        );
    }

    public function testSetDataCollectionWithLinks()
    {
        $object = [
            'code' => 123,
            'name' => 'Name'
        ];

        $metadata = $this->getEntityMetadata('Test\Entity', ['code']);
        $metadata->addField($this->createFieldMetadata('code'));
        $metadata->addField($this->createFieldMetadata('name'));
        $metadata->addLink(
            'self',
            new ExternalLinkMetadata('/api/{__type__}/{id}', ['__type__' => null, 'id' => '__id__'])
        );
        $linkWithMeta = $metadata->addLink(
            'with_meta',
            new ExternalLinkMetadata('/api/{type}/{id}/meta', ['type' => '__type__', 'id' => '__id__'])
        );
        $linkWithMeta->addMetaProperty(new MetaAttributeMetadata('meta1', 'string', 'name'));
        $linkWithMeta->addMetaProperty(new MetaAttributeMetadata('meta2', 'string'));
        $linkWithMeta->addMetaProperty(new MetaAttributeMetadata('meta3', 'string', '__class__'));
        $metadata->addLink(
            'unresolved',
            new ExternalLinkMetadata('/api/{unknown}', ['unknown' => null])
        );

        $this->logger->expects(self::once())
            ->method('notice')
            ->with('Cannot build URL for a link. Missing Parameters: unknown.');

        $this->documentBuilder->setDataCollection([$object], $this->requestType, $metadata);
        self::assertEquals(
            [
                [
                    'code' => 'Test\Entity::123',
                    'name' => 'Name',
                    'links' => [
                        'self' => '/api/test_entity/Test\Entity::123',
                        'with_meta' => [
                            'href' => '/api/test_entity/Test\Entity::123/meta',
                            'meta1' => 'Name',
                            'meta3' => 'Test\Entity'
                        ]
                    ]
                ]
            ],
            $this->documentBuilder->getDocument()
        );
    }

    public function testAssociationWithMetaPropertiesInTargetMetadata()
    {
        $object = [
            'code'       => 123,
            'category'   => ['code' => 456, '_meta1' => 'category_meta1'],
            'categories' => [
                ['code' => 456, '_meta1' => 'category_meta1_item1', 'meta2' => 'category_meta2_item1'],
                ['code' => 457, '_meta1' => 'category_meta1_item2']
            ]
        ];

        $metadata = $this->getEntityMetadata('Test\Entity', ['code']);
        $metadata->addField($this->createFieldMetadata('code'));
        $metadata->addAssociation($this->createAssociationMetadata('category', 'Test\Category', false, ['code']));
        $metadata->addAssociation($this->createAssociationMetadata('categories', 'Test\Category', true, ['code']));

        foreach ($metadata->getAssociations() as $association) {
            $associationTargetMetadata = $association->getTargetMetadata();
            $associationTargetMetadata->addMetaProperty(new MetaPropertyMetadata('meta1', 'string', '_meta1'));
            $associationTargetMetadata->addMetaProperty(new MetaPropertyMetadata('meta2', 'string'));
        }

        $this->documentBuilder->setDataObject($object, $this->requestType, $metadata);
        self::assertEquals(
            [
                'code'       => 'Test\Entity::123',
                'category'   => [
                    'code' => 'Test\Category::456'
                ],
                'categories' => [
                    [
                        'code' => 'Test\Category::456'
                    ],
                    [
                        'code' => 'Test\Category::457'
                    ]
                ]
            ],
            $this->documentBuilder->getDocument()
        );
    }

    public function testAssociationWithAssociationLevelMetaPropertiesInTargetMetadata()
    {
        $object = [
            'code'       => 123,
            'category'   => ['code' => 456, '_meta1' => 'category_meta1'],
            'categories' => [
                ['code' => 456, '_meta1' => 'category_meta1_item1', 'meta2' => 'category_meta2_item1'],
                ['code' => 457, '_meta1' => 'category_meta1_item2']
            ]
        ];

        $metadata = $this->getEntityMetadata('Test\Entity', ['code']);
        $metadata->addField($this->createFieldMetadata('code'));
        $metadata->addAssociation($this->createAssociationMetadata('category', 'Test\Category', false, ['code']));
        $metadata->addAssociation($this->createAssociationMetadata('categories', 'Test\Category', true, ['code']));

        foreach ($metadata->getAssociations() as $association) {
            $associationTargetMetadata = $association->getTargetMetadata();
            $associationTargetMetadata->addMetaProperty(new MetaPropertyMetadata('meta1', 'string', '_meta1'))
                ->setAssociationLevel(true);
            $associationTargetMetadata->addMetaProperty(new MetaPropertyMetadata('meta2', 'string'))
                ->setAssociationLevel(true);
        }

        $this->documentBuilder->setDataObject($object, $this->requestType, $metadata);
        self::assertEquals(
            [
                'code'          => 'Test\Entity::123',
                'category'   => [
                    'code' => 'Test\Category::456',
                    'meta1' => 'category_meta1'
                ],
                'categories' => [
                    [
                        'code' => 'Test\Category::456',
                        'meta1' => 'category_meta1_item1',
                        'meta2' => 'category_meta2_item1'
                    ],
                    [
                        'code' => 'Test\Category::457',
                        'meta1' => 'category_meta1_item2'
                    ]
                ]
            ],
            $this->documentBuilder->getDocument()
        );
    }

    public function testSetDataCollectionOfScalarsWithMetadata(): void
    {
        $metadata = $this->getEntityMetadata('Test\Entity', ['id']);
        $metadata->addField($this->createFieldMetadata('id'));
        $metadata->addField($this->createFieldMetadata('name'));

        $this->documentBuilder->setDataCollection(['val1', null, 'val3'], $this->requestType, $metadata);
        self::assertEquals(
            ['val1', null, 'val3'],
            $this->documentBuilder->getDocument()
        );
    }

    public function testAssociationWithInheritance()
    {
        $object = [
            'id' => 123,
            'categories' => [
                ['id' => 456, '__class__' => 'Test\Category1', 'name' => 'Category1'],
                ['id' => 457, '__class__' => 'Test\Category2', 'name' => 'Category2']
            ]
        ];

        $metadata = $this->getEntityMetadata('Test\Entity', ['id']);
        $metadata->addField($this->createFieldMetadata('id'));
        $categoriesMetadata = $metadata->addAssociation(
            $this->createAssociationMetadata('categories', 'Test\CategoryWithoutAlias', true)
        );
        $categoriesMetadata->getTargetMetadata()->setInheritedType(true);
        $categoriesMetadata->setAcceptableTargetClassNames(['Test\Category1', 'Test\Category2']);
        $categoriesMetadata->getTargetMetadata()->addField($this->createFieldMetadata('name'));
        $categoriesMetadata->getTargetMetadata()
            ->addMetaProperty($this->createMetaPropertyMetadata(ConfigUtil::CLASS_NAME))
            ->setResultName('meta_class');

        $this->documentBuilder->setDataObject($object, $this->requestType, $metadata);
        self::assertEquals(
            [
                'id' => 'Test\Entity::123',
                'categories' => [
                    [
                        'entity' => 'Test\Category1',
                        'id' => 'Test\CategoryWithoutAlias::456',
                        'name' => 'Category1'
                    ],
                    [
                        'entity' => 'Test\Category2',
                        'id' => 'Test\CategoryWithoutAlias::457',
                        'name' => 'Category2'
                    ]
                ]
            ],
            $this->documentBuilder->getDocument()
        );
    }

    public function testAssociationWithInheritanceAndSomeInheritedEntitiesDoNotHaveAlias()
    {
        $object = [
            'id' => 123,
            'categories' => [
                ['id' => 456, '__class__' => 'Test\Category1', 'name' => 'Category1'],
                ['id' => 457, '__class__' => 'Test\Category2WithoutAlias', 'name' => 'Category2']
            ]
        ];

        $metadata = $this->getEntityMetadata('Test\Entity', ['id']);
        $metadata->addField($this->createFieldMetadata('id'));
        $categoriesMetadata = $metadata->addAssociation(
            $this->createAssociationMetadata('categories', 'Test\Category', true)
        );
        $categoriesMetadata->getTargetMetadata()->setInheritedType(true);
        $categoriesMetadata->setAcceptableTargetClassNames(['Test\Category1', 'Test\Category2WithoutAlias']);
        $categoriesMetadata->getTargetMetadata()->addField($this->createFieldMetadata('name'));
        $categoriesMetadata->getTargetMetadata()
            ->addMetaProperty($this->createMetaPropertyMetadata(ConfigUtil::CLASS_NAME))
            ->setResultName('meta_class');

        $this->documentBuilder->setDataObject($object, $this->requestType, $metadata);
        self::assertEquals(
            [
                'id' => 'Test\Entity::123',
                'categories' => [
                    [
                        'entity' => 'Test\Category1',
                        'id' => 'Test\Category::456',
                        'name' => 'Category1'
                    ],
                    [
                        'entity' => 'Test\Category2WithoutAlias',
                        'id' => 'Test\Category::457',
                        'name' => 'Category2'
                    ]
                ]
            ],
            $this->documentBuilder->getDocument()
        );
    }

    public function testMissingAssociationsAsFields()
    {
        $object = [
            'id' => 123
        ];

        $metadata = $this->getEntityMetadata('Test\Entity', ['id']);
        $metadata->addField($this->createFieldMetadata('id'));
        $metadata->addAssociation($this->createAssociationMetadata('missingToOne', 'Test\Class'));
        $metadata->addAssociation($this->createAssociationMetadata('missingToMany', 'Test\Class', true));
        foreach ($metadata->getAssociations() as $association) {
            $association->setDataType('array');
        }

        $this->documentBuilder->setDataObject($object, $this->requestType, $metadata);
        self::assertEquals(
            [
                'id' => 'Test\Entity::123',
                'missingToOne' => null,
                'missingToMany' => []
            ],
            $this->documentBuilder->getDocument()
        );
    }

    /**
     * @dataProvider toOneAssociationAsFieldProvider
     */
    public function testToOneAssociationAsField(array|int|null $value, array|int|null $expected)
    {
        $object = [
            'id' => 123,
            'category' => $value
        ];

        $metadata = $this->getEntityMetadata('Test\Entity', ['id']);
        $metadata->addField($this->createFieldMetadata('id'));
        $association = $metadata->addAssociation(
            $this->createAssociationMetadata('category', 'Test\Category')
        );
        $association->setDataType('object');
        $association->getTargetMetadata()->addField($this->createFieldMetadata('name'));

        $this->documentBuilder->setDataObject($object, $this->requestType, $metadata);
        self::assertEquals(
            [
                'id' => 'Test\Entity::123',
                'category' => $expected
            ],
            $this->documentBuilder->getDocument()
        );
    }

    public function toOneAssociationAsFieldProvider(): array
    {
        return [
            [null, null],
            [123, 123],
            [
                ['id' => 123],
                ['id' => 123, 'name' => null]
            ],
            [
                ['id' => 123, 'name' => 'name1'],
                ['id' => 123, 'name' => 'name1']
            ],
            [
                ['id' => 123, 'name' => 'name1', 'other' => 'val1'],
                ['id' => 123, 'name' => 'name1']
            ]
        ];
    }

    /**
     * @dataProvider toManyAssociationAsFieldProvider
     */
    public function testToManyAssociationAsField(?array $value, array $expected)
    {
        $object = [
            'id' => 123,
            'categories' => $value
        ];

        $metadata = $this->getEntityMetadata('Test\Entity', ['id']);
        $metadata->addField($this->createFieldMetadata('id'));
        $association = $metadata->addAssociation(
            $this->createAssociationMetadata('categories', 'Test\Category', true)
        );
        $association->setDataType('array');
        $association->getTargetMetadata()->addField($this->createFieldMetadata('name'));

        $this->documentBuilder->setDataObject($object, $this->requestType, $metadata);
        self::assertEquals(
            [
                'id' => 'Test\Entity::123',
                'categories' => $expected
            ],
            $this->documentBuilder->getDocument()
        );
    }

    public function toManyAssociationAsFieldProvider(): array
    {
        return [
            [null, []],
            [[], []],
            [[123, 124], [123, 124]],
            [
                [['id' => 123], ['id' => 124]],
                [['id' => 123, 'name' => null], ['id' => 124, 'name' => null]]
            ],
            [
                [['id' => 123, 'name' => 'name1'], ['id' => 124, 'name' => 'name2']],
                [['id' => 123, 'name' => 'name1'], ['id' => 124, 'name' => 'name2']]
            ],
            [
                [
                    ['id' => 123, 'name' => 'name1', 'other' => 'val1'],
                    ['id' => 124, 'name' => 'name2', 'other' => 'val1']
                ],
                [['id' => 123, 'name' => 'name1'], ['id' => 124, 'name' => 'name2']]
            ]
        ];
    }

    /**
     * @dataProvider toOneAssociationAsFieldForIdFieldsOnlyProvider
     */
    public function testToOneAssociationAsFieldForIdFieldsOnly(array|int|null $value, array|int|null $expected)
    {
        $object = [
            'id' => 123,
            'category' => $value
        ];

        $metadata = $this->getEntityMetadata('Test\Entity', ['id']);
        $metadata->addField($this->createFieldMetadata('id'));
        $association = $metadata->addAssociation(
            $this->createAssociationMetadata('category', 'Test\Category')
        );
        $association->setDataType('scalar');

        $this->documentBuilder->setDataObject($object, $this->requestType, $metadata);
        self::assertEquals(
            [
                'id' => 'Test\Entity::123',
                'category' => $expected
            ],
            $this->documentBuilder->getDocument()
        );
    }

    public function toOneAssociationAsFieldForIdFieldsOnlyProvider(): array
    {
        return [
            [null, null],
            [123, 123],
            [['id' => 123], 123],
            [['id' => 123, 'name' => 'name1'], 123]
        ];
    }

    /**
     * @dataProvider toManyAssociationAsFieldForIdFieldsOnlyProvider
     */
    public function testToManyAssociationAsFieldForIdFieldsOnly(?array $value, array $expected)
    {
        $object = [
            'id' => 123,
            'categories' => $value
        ];

        $metadata = $this->getEntityMetadata('Test\Entity', ['id']);
        $metadata->addField($this->createFieldMetadata('id'));
        $association = $metadata->addAssociation(
            $this->createAssociationMetadata('categories', 'Test\Category', true)
        );
        $association->setDataType('array');

        $this->documentBuilder->setDataObject($object, $this->requestType, $metadata);
        self::assertEquals(
            [
                'id' => 'Test\Entity::123',
                'categories' => $expected
            ],
            $this->documentBuilder->getDocument()
        );
    }

    public function toManyAssociationAsFieldForIdFieldsOnlyProvider(): array
    {
        return [
            [null, []],
            [[], []],
            [[123, 124], [123, 124]],
            [
                [['id' => 123], ['id' => 124]],
                [123, 124]
            ],
            [
                [['id' => 123, 'name' => 'name1'], ['id' => 124, 'name' => 'name2']],
                [123, 124]
            ]
        ];
    }

    /**
     * @dataProvider toOneCollapsedAssociationAsFieldProvider
     */
    public function testToOneCollapsedAssociationAsField(array|string|null $value, ?string $expected)
    {
        $object = [
            'id' => 123,
            'category' => $value
        ];

        $metadata = $this->getEntityMetadata('Test\Entity', ['id']);
        $metadata->addField($this->createFieldMetadata('id'));
        $association = $metadata->addAssociation(
            $this->createAssociationMetadata('category', 'Test\Category')
        );
        $association->setDataType('scalar');
        $association->setCollapsed();
        $association->getTargetMetadata()->removeField('id');
        $association->getTargetMetadata()->addField($this->createFieldMetadata('name'));

        $this->documentBuilder->setDataObject($object, $this->requestType, $metadata);
        self::assertEquals(
            [
                'id' => 'Test\Entity::123',
                'category' => $expected
            ],
            $this->documentBuilder->getDocument()
        );
    }

    public function toOneCollapsedAssociationAsFieldProvider(): array
    {
        return [
            [null, null],
            ['name1', 'name1'],
            [
                ['name' => 'name1'],
                'name1'
            ],
            [
                ['name' => 'name1', 'other' => 'val1'],
                'name1'
            ]
        ];
    }

    /**
     * @dataProvider toManyCollapsedAssociationAsFieldProvider
     */
    public function testToManyCollapsedAssociationAsField(?array $value, array $expected)
    {
        $object = [
            'id' => 123,
            'categories' => $value
        ];

        $metadata = $this->getEntityMetadata('Test\Entity', ['id']);
        $metadata->addField($this->createFieldMetadata('id'));
        $association = $metadata->addAssociation(
            $this->createAssociationMetadata('categories', 'Test\Category', true)
        );
        $association->setDataType('array');
        $association->setCollapsed();
        $association->getTargetMetadata()->removeField('id');
        $association->getTargetMetadata()->addField($this->createFieldMetadata('name'));

        $this->documentBuilder->setDataObject($object, $this->requestType, $metadata);
        self::assertEquals(
            [
                'id' => 'Test\Entity::123',
                'categories' => $expected
            ],
            $this->documentBuilder->getDocument()
        );
    }

    public function toManyCollapsedAssociationAsFieldProvider(): array
    {
        return [
            [null, []],
            [[], []],
            [['name1', 'name2'], ['name1', 'name2']],
            [
                [['name' => 'name1'], ['name' => 'name2']],
                ['name1', 'name2']
            ],
            [
                [
                    ['name' => 'name1', 'other' => 'val1'],
                    ['name' => 'name2', 'other' => 'val1']
                ],
                ['name1', 'name2']
            ]
        ];
    }

    public function testNestedAssociationAsArrayAttribute()
    {
        $object = [
            'id' => 1,
            'association' => [
                'id' => 123,
                'name' => 'Name',
                'meta1' => 'Meta1',
                'category' => 456,
                'group' => null,
                'role' => ['id' => 789],
                'categories' => [
                    ['id' => 456],
                    ['id' => 457]
                ],
                'groups' => null,
                'products' => [],
                'roles' => [
                    ['id' => 789, 'name' => 'Role1'],
                    ['id' => 780, 'name' => 'Role2']
                ]
            ]
        ];

        $targetMetadata = $this->getEntityMetadata('Test\Target', ['id']);
        $targetMetadata->addField($this->createFieldMetadata('id'));
        $targetMetadata->addField($this->createFieldMetadata('name'));
        $targetMetadata->addMetaProperty($this->createMetaPropertyMetadata('meta1'));
        $targetMetadata->addAssociation($this->createAssociationMetadata('category', 'Test\Category'));
        $targetMetadata->addAssociation($this->createAssociationMetadata('group', 'Test\Groups'));
        $targetMetadata->addAssociation($this->createAssociationMetadata('role', 'Test\Role'));
        $targetMetadata->addAssociation($this->createAssociationMetadata('categories', 'Test\Category', true));
        $targetMetadata->addAssociation($this->createAssociationMetadata('groups', 'Test\Group', true));
        $targetMetadata->addAssociation($this->createAssociationMetadata('products', 'Test\Product', true));
        $targetMetadata->addAssociation($this->createAssociationMetadata('roles', 'Test\Role', true));
        $targetMetadata->getAssociation('roles')->getTargetMetadata()->addField($this->createFieldMetadata('name'));
        $targetMetadata->addAssociation($this->createAssociationMetadata('missingToOne', 'Test\Class'));
        $targetMetadata->addAssociation($this->createAssociationMetadata('missingToMany', 'Test\Class', true));

        $metadata = $this->getEntityMetadata('Test\Entity', ['id']);
        $associationMetadata = $metadata->addAssociation(
            $this->createAssociationMetadata('association', 'Test\Target')
        );
        $associationMetadata->setTargetMetadata($targetMetadata);
        $associationMetadata->setDataType('array');

        $this->documentBuilder->setDataObject($object, $this->requestType, $metadata);
        self::assertEquals(
            [
                'id' => 'Test\Entity::1',
                'association' => [
                    'id' => 123,
                    'name' => 'Name',
                    'meta1' => 'Meta1',
                    'category' => 456,
                    'group' => null,
                    'role' => 789,
                    'categories' => [456, 457],
                    'groups' => [],
                    'products' => [],
                    'roles' => [
                        ['id' => 789, 'name' => 'Role1'],
                        ['id' => 780, 'name' => 'Role2']
                    ],
                    'missingToOne' => null,
                    'missingToMany' => []
                ]
            ],
            $this->documentBuilder->getDocument()
        );
    }

    public function testNestedObject()
    {
        $object = [
            'id' => 1,
            'nested' => [
                'name' => 'Name'
            ]
        ];

        $metadata = $this->getEntityMetadata('Test\Entity', ['id']);
        $metadata->addField($this->createFieldMetadata('id'));
        $nestedObject = $metadata->addAssociation(
            $this->createAssociationMetadata('nested', 'Test\NestedWithoutAlias', false, [])
        );
        $nestedObject->setDataType(DataType::NESTED_OBJECT);
        $nestedObject->setPropertyPath(ConfigUtil::IGNORE_PROPERTY_PATH);
        $nestedObject->getTargetMetadata()->addField($this->createFieldMetadata('name'));

        $this->documentBuilder->setDataObject($object, $this->requestType, $metadata);
        self::assertEquals(
            [
                'id' => 'Test\Entity::1',
                'nested' => [
                    'name' => 'Name'
                ]
            ],
            $this->documentBuilder->getDocument()
        );
    }

    public function testSetErrorObject()
    {
        $error = new Error();
        $error->setStatusCode(500);
        $error->setCode('errCode');
        $error->setTitle('some error');
        $error->setDetail('some error details');

        $this->documentBuilder->setErrorObject($error);
        self::assertEquals(
            [
                [
                    'code' => 'errCode',
                    'title' => 'some error',
                    'detail' => 'some error details'
                ]
            ],
            $this->documentBuilder->getDocument()
        );
    }

    public function testSetErrorObjectForErrorWithMetaProperty()
    {
        $error = new Error();
        $error->setStatusCode(500);
        $error->setCode('errCode');
        $error->setTitle('some error');
        $error->setDetail('some error details');
        $error->addMetaProperty('meta1', new ErrorMetaProperty('val1'));

        $this->documentBuilder->setErrorObject($error);
        self::assertEquals(
            [
                [
                    'code' => 'errCode',
                    'title' => 'some error',
                    'detail' => 'some error details',
                    'properties' => [
                        'meta1' => 'val1'
                    ]
                ]
            ],
            $this->documentBuilder->getDocument()
        );
    }

    public function testSetErrorCollection()
    {
        $error1 = new Error();
        $error1->setStatusCode(500);
        $error1->setCode('errCode');
        $error1->setTitle('some error');
        $error1->setDetail('some error details');

        $error2 = new Error();
        $error2->setStatusCode(400);
        $error2->setTitle('some error with meta properties');
        $error2->setDetail('some error details with meta properties');
        $error2->addMetaProperty('meta1', new ErrorMetaProperty('val1'));

        $this->documentBuilder->setErrorCollection([$error1, $error2]);
        self::assertEquals(
            [
                [
                    'code' => 'errCode',
                    'title' => 'some error',
                    'detail' => 'some error details'
                ],
                [
                    'title' => 'some error with meta properties',
                    'detail' => 'some error details with meta properties',
                    'properties' => [
                        'meta1' => 'val1'
                    ]
                ]
            ],
            $this->documentBuilder->getDocument()
        );
    }

    public function testMetaPropertyWithResultName()
    {
        $object = [
            'id' => 123,
            'meta1' => 'Meta1'
        ];

        $metadata = $this->getEntityMetadata('Test\Entity', ['id']);
        $metadata->addField($this->createFieldMetadata('id'));
        $metadata->addMetaProperty($this->createMetaPropertyMetadata('meta1'))
            ->setResultName('resultMeta1');

        $this->documentBuilder->setDataObject($object, $this->requestType, $metadata);
        self::assertEquals(
            [
                'id' => 'Test\Entity::123',
                'resultMeta1' => 'Meta1'
            ],
            $this->documentBuilder->getDocument()
        );
    }

    public function testMetaPropertyThatNotMappedToAnyField()
    {
        $object = [
            'id' => 123,
            'meta1' => 'Meta1'
        ];

        $metadata = $this->getEntityMetadata('Test\Entity', ['id']);
        $metadata->addField($this->createFieldMetadata('id'));
        $metadata->addMetaProperty($this->createMetaPropertyMetadata('meta1'))
            ->setPropertyPath(ConfigUtil::IGNORE_PROPERTY_PATH);

        $this->documentBuilder->setDataObject($object, $this->requestType, $metadata);
        self::assertEquals(
            [
                'id' => 'Test\Entity::123'
            ],
            $this->documentBuilder->getDocument()
        );
    }

    public function testSetDataObjectForEntityWithoutIdentifier()
    {
        $object = [
            'name' => 'Name',
            'meta1' => 'Meta1',
            'category' => 456,
            'categories' => [
                ['id' => 456],
                ['id' => 457]
            ]
        ];

        $metadata = $this->getEntityMetadata('Test\Entity', []);
        $metadata->addField($this->createFieldMetadata('name'));
        $metadata->addMetaProperty($this->createMetaPropertyMetadata('meta1'));
        $metadata->addAssociation($this->createAssociationMetadata('category', 'Test\Category'));
        $metadata->addAssociation($this->createAssociationMetadata('categories', 'Test\Category', true));

        $this->documentBuilder->setDataObject($object, $this->requestType, $metadata);
        self::assertEquals(
            [
                'name' => 'Name',
                'meta1' => 'Meta1',
                'category' => 'Test\Category::456',
                'categories' => [456, 457]
            ],
            $this->documentBuilder->getDocument()
        );
    }

    public function testInputOnlyMetaProperty()
    {
        $object = [
            'id' => 123,
            'meta1' => 'Meta1'
        ];

        $metadata = $this->getEntityMetadata('Test\Entity', ['id']);
        $metadata->addField($this->createFieldMetadata('id'));
        $metadata->addMetaProperty($this->createMetaPropertyMetadata('meta1'))
            ->setDirection(true, false);

        $this->documentBuilder->setDataObject($object, $this->requestType, $metadata);
        self::assertEquals(
            [
                'id' => 'Test\Entity::123'
            ],
            $this->documentBuilder->getDocument()
        );
    }

    public function testInputOnlyField()
    {
        $object = [
            'id' => 123,
            'field1' => 'value1'
        ];

        $metadata = $this->getEntityMetadata('Test\Entity', ['id']);
        $metadata->addField($this->createFieldMetadata('id'));
        $metadata->addField($this->createFieldMetadata('field1'))
            ->setDirection(true, false);

        $this->documentBuilder->setDataObject($object, $this->requestType, $metadata);
        self::assertEquals(
            [
                'id' => 'Test\Entity::123'
            ],
            $this->documentBuilder->getDocument()
        );
    }

    public function testInputOnlyAssociation()
    {
        $object = [
            'id' => 123,
            'association1' => 456
        ];

        $metadata = $this->getEntityMetadata('Test\Entity', ['id']);
        $metadata->addField($this->createFieldMetadata('id'));
        $metadata->addAssociation($this->createAssociationMetadata('association1', 'Test\Category'))
            ->setDirection(true, false);

        $this->documentBuilder->setDataObject($object, $this->requestType, $metadata);
        self::assertEquals(
            [
                'id' => 'Test\Entity::123'
            ],
            $this->documentBuilder->getDocument()
        );
    }

    public function testInputOnlyMetaPropertyForEntityWithoutIdentifier()
    {
        $object = [
            'meta1' => 'Meta1'
        ];

        $metadata = $this->getEntityMetadata('Test\Entity', []);
        $metadata->addMetaProperty($this->createMetaPropertyMetadata('meta1'))
            ->setDirection(true, false);

        $this->documentBuilder->setDataObject($object, $this->requestType, $metadata);
        self::assertEquals(
            [],
            $this->documentBuilder->getDocument()
        );
    }

    public function testInputOnlyFieldForEntityWithoutIdentifier()
    {
        $object = [
            'field1' => 'value1'
        ];

        $metadata = $this->getEntityMetadata('Test\Entity', []);
        $metadata->addField($this->createFieldMetadata('field1'))
            ->setDirection(true, false);

        $this->documentBuilder->setDataObject($object, $this->requestType, $metadata);
        self::assertEquals(
            [],
            $this->documentBuilder->getDocument()
        );
    }

    public function testInputOnlyAssociationForEntityWithoutIdentifier()
    {
        $object = [
            'association1' => 456
        ];

        $metadata = $this->getEntityMetadata('Test\Entity', []);
        $metadata->addAssociation($this->createAssociationMetadata('association1', 'Test\Category'))
            ->setDirection(true, false);

        $this->documentBuilder->setDataObject($object, $this->requestType, $metadata);
        self::assertEquals(
            [],
            $this->documentBuilder->getDocument()
        );
    }

    public function testAddLinksToCollectionValuedResult()
    {
        $object = [
            'id' => 1,
            'role' => ['id' => 21, 'name' => 'Role1', 'users' => [211, 212]],
            'roles' => [
                ['id' => 21, 'name' => 'Role1', 'users' => [211, 212]],
                ['id' => 22, 'name' => 'Role2', 'users' => [213, 214]]
            ]
        ];

        $metadata = $this->getEntityMetadata('Test\Entity', ['id']);
        $metadata->addField($this->createFieldMetadata('id'));
        $metadata->addAssociation($this->createAssociationMetadata('role', 'Test\Role'));
        $metadata->addAssociation($this->createAssociationMetadata('roles', 'Test\Role', true));
        $roleMetadata = $this->getEntityMetadata('Test\Role', ['id']);
        $roleMetadata->addField($this->createFieldMetadata('name'));
        $roleMetadata->addAssociation($this->createAssociationMetadata('users', 'Test\User', true));
        $metadata->getAssociation('role')->setTargetMetadata($roleMetadata);
        $metadata->getAssociation('roles')->setTargetMetadata($roleMetadata);

        $this->documentBuilder->addLinkMetadata('link10', new ExternalLinkMetadata('/api/test'));
        $this->documentBuilder->setDataCollection([$object], $this->requestType, $metadata);
        $this->documentBuilder->addLink('link1', 'link1_url');
        $this->documentBuilder->addLink('link2', 'link2_url', ['key' => 'value']);

        self::assertEquals(
            [
                [
                    'id' => 'Test\Entity::1',
                    'role' => [
                        'id' => 'Test\Role::21',
                        'name' => 'Role1',
                        'users' => ['Test\User::211', 'Test\User::212']
                    ],
                    'roles' => [
                        [
                            'id' => 'Test\Role::21',
                            'name' => 'Role1',
                            'users' => ['Test\User::211', 'Test\User::212']
                        ],
                        [
                            'id' => 'Test\Role::22',
                            'name' => 'Role2',
                            'users' => ['Test\User::213', 'Test\User::214']
                        ]
                    ]
                ]
            ],
            $this->documentBuilder->getDocument()
        );
    }

    public function testAddLinksToSingleValuedResult()
    {
        $object = [
            'id' => 1,
            'role' => ['id' => 21, 'name' => 'Role1', 'users' => [211, 212]],
            'roles' => [
                ['id' => 21, 'name' => 'Role1', 'users' => [211, 212]],
                ['id' => 22, 'name' => 'Role2', 'users' => [213, 214]]
            ]
        ];

        $metadata = $this->getEntityMetadata('Test\Entity', ['id']);
        $metadata->addField($this->createFieldMetadata('id'));
        $metadata->addAssociation($this->createAssociationMetadata('role', 'Test\Role'));
        $metadata->addAssociation($this->createAssociationMetadata('roles', 'Test\Role', true));
        $roleMetadata = $this->getEntityMetadata('Test\Role', ['id']);
        $roleMetadata->addField($this->createFieldMetadata('name'));
        $roleMetadata->addAssociation($this->createAssociationMetadata('users', 'Test\User', true));
        $metadata->getAssociation('role')->setTargetMetadata($roleMetadata);
        $metadata->getAssociation('roles')->setTargetMetadata($roleMetadata);

        $this->documentBuilder->addLinkMetadata('link10', new ExternalLinkMetadata('/api/test'));
        $this->documentBuilder->setDataObject($object, $this->requestType, $metadata);
        $this->documentBuilder->addLink('link1', 'link1_url');
        $this->documentBuilder->addLink('link2', 'link2_url', ['key' => 'value']);

        self::assertEquals(
            [
                'id' => 'Test\Entity::1',
                'role' => [
                    'id' => 'Test\Role::21',
                    'name' => 'Role1',
                    'users' => ['Test\User::211', 'Test\User::212']
                ],
                'roles' => [
                    [
                        'id' => 'Test\Role::21',
                        'name' => 'Role1',
                        'users' => ['Test\User::211', 'Test\User::212']
                    ],
                    [
                        'id' => 'Test\Role::22',
                        'name' => 'Role2',
                        'users' => ['Test\User::213', 'Test\User::214']
                    ]
                ]
            ],
            $this->documentBuilder->getDocument()
        );
    }

    public function testMetadataForCollectionValuedResult()
    {
        $object = [
            'id' => 1,
            'name' => 'test'
        ];

        $metadata = $this->getEntityMetadata('Test\Entity', ['id']);
        $metadata->addField($this->createFieldMetadata('id'));
        $metadata->addField($this->createFieldMetadata('name'));

        $this->documentBuilder->setMetadata([
            '' => ['has_more' => true],
            '0.userRoles' => ['has_more' => true],
            'meta1' => 'some value'
        ]);
        $this->documentBuilder->setDataCollection([$object], $this->requestType, $metadata);

        self::assertEquals(
            [
                [
                    'id' => 'Test\Entity::1',
                    'name' => 'test'
                ]
            ],
            $this->documentBuilder->getDocument()
        );
    }
}
