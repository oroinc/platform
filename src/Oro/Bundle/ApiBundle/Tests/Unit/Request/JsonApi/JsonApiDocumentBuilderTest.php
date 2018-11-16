<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Request\JsonApi;

use Oro\Bundle\ApiBundle\Metadata\ExternalLinkMetadata;
use Oro\Bundle\ApiBundle\Metadata\MetaAttributeMetadata;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Tests\Unit\Request\DocumentBuilderTestCase;
use Psr\Log\LoggerInterface;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class JsonApiDocumentBuilderTest extends DocumentBuilderTestCase
{
    /** @var JsonApiDocumentBuilder */
    private $documentBuilder;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    protected function setUp()
    {
        $this->requestType = new RequestType([RequestType::REST, RequestType::JSON_API]);
        $valueNormalizer = $this->getValueNormalizer();
        $entityIdTransformer = $this->getEntityIdTransformer();
        $entityIdTransformerRegistry = $this->getEntityIdTransformerRegistry($entityIdTransformer);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->documentBuilder = new JsonApiDocumentBuilder(
            $valueNormalizer,
            $entityIdTransformerRegistry,
            $this->logger
        );
    }

    public function testSetDataObjectWithoutMetadata()
    {
        $object = [
            'id'   => 123,
            'name' => 'Name'
        ];

        $this->documentBuilder->setDataObject($object, $this->requestType);

        self::assertEquals(
            [
                'meta' => [
                    'id'   => 123,
                    'name' => 'Name'
                ]
            ],
            $this->documentBuilder->getDocument()
        );
    }

    public function testSetDataCollectionWithoutMetadata()
    {
        $object = [
            'id'   => 123,
            'name' => 'Name'
        ];

        $this->documentBuilder->setDataCollection([$object], $this->requestType);

        self::assertEquals(
            [
                'meta' => [
                    'data' => [
                        [
                            'id'   => 123,
                            'name' => 'Name'
                        ]
                    ]
                ]
            ],
            $this->documentBuilder->getDocument()
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testSetDataObjectWithMetadata()
    {
        $object = [
            'id'         => 123,
            'name'       => 'Name',
            'meta1'      => 'Meta1',
            'category'   => 456,
            'group'      => null,
            'role'       => ['id' => 789],
            'categories' => [
                ['id' => 456],
                ['id' => 457]
            ],
            'groups'     => null,
            'products'   => [],
            'roles'      => [
                ['id' => 789, 'name' => 'Role1'],
                ['id' => 780, 'name' => 'Role2']
            ],
            'otherRoles' => [ // this is used to test that "included" collection does not contain duplicates
                ['id' => 789, 'name' => 'Role1'],
                ['id' => 780, 'name' => 'Role2']
            ],
            'unknown'    => 'test'
        ];

        $metadata = $this->getEntityMetadata('Test\Entity', ['id']);
        $metadata->addField($this->createFieldMetadata('id'));
        $metadata->addField($this->createFieldMetadata('name'));
        $metadata->addField($this->createFieldMetadata('missingField'));
        $metadata->addMetaProperty($this->createMetaPropertyMetadata('meta1'));
        $metadata->addMetaProperty($this->createMetaPropertyMetadata('missingMeta'));
        $metadata->addAssociation($this->createAssociationMetadata('category', 'Test\Category'));
        $metadata->addAssociation($this->createAssociationMetadata('group', 'Test\Groups'));
        $metadata->addAssociation($this->createAssociationMetadata('role', 'Test\Role'));
        $metadata->addAssociation($this->createAssociationMetadata('categories', 'Test\Category', true));
        $metadata->addAssociation($this->createAssociationMetadata('groups', 'Test\Group', true));
        $metadata->addAssociation($this->createAssociationMetadata('products', 'Test\Product', true));
        $metadata->addAssociation($this->createAssociationMetadata('roles', 'Test\Role', true));
        $metadata->getAssociation('roles')->getTargetMetadata()->addField($this->createFieldMetadata('name'));
        $metadata->addAssociation($this->createAssociationMetadata('otherRoles', 'Test\Role', true));
        $metadata->getAssociation('otherRoles')->getTargetMetadata()->addField($this->createFieldMetadata('name'));
        $metadata->addAssociation($this->createAssociationMetadata('missingToOne', 'Test\Class'));
        $metadata->addAssociation($this->createAssociationMetadata('missingToMany', 'Test\Class', true));

        $this->documentBuilder->setDataObject($object, $this->requestType, $metadata);
        self::assertEquals(
            [
                'data'     => [
                    'type'          => 'test_entity',
                    'id'            => 'Test\Entity::123',
                    'meta'          => [
                        'meta1' => 'Meta1'
                    ],
                    'attributes'    => [
                        'name'         => 'Name',
                        'missingField' => null
                    ],
                    'relationships' => [
                        'category'      => [
                            'data' => [
                                'type' => 'test_category',
                                'id'   => 'Test\Category::456'
                            ]
                        ],
                        'group'         => [
                            'data' => null
                        ],
                        'role'          => [
                            'data' => [
                                'type' => 'test_role',
                                'id'   => 'Test\Role::789'
                            ]
                        ],
                        'categories'    => [
                            'data' => [
                                [
                                    'type' => 'test_category',
                                    'id'   => 'Test\Category::456'
                                ],
                                [
                                    'type' => 'test_category',
                                    'id'   => 'Test\Category::457'
                                ]
                            ]
                        ],
                        'groups'        => [
                            'data' => []
                        ],
                        'products'      => [
                            'data' => []
                        ],
                        'roles'         => [
                            'data' => [
                                [
                                    'type' => 'test_role',
                                    'id'   => 'Test\Role::789'
                                ],
                                [
                                    'type' => 'test_role',
                                    'id'   => 'Test\Role::780'
                                ]
                            ]
                        ],
                        'otherRoles'    => [
                            'data' => [
                                [
                                    'type' => 'test_role',
                                    'id'   => 'Test\Role::789'
                                ],
                                [
                                    'type' => 'test_role',
                                    'id'   => 'Test\Role::780'
                                ]
                            ]
                        ],
                        'missingToOne'  => [
                            'data' => null
                        ],
                        'missingToMany' => [
                            'data' => []
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type'       => 'test_role',
                        'id'         => 'Test\Role::789',
                        'attributes' => [
                            'name' => 'Role1'
                        ]
                    ],
                    [
                        'type'       => 'test_role',
                        'id'         => 'Test\Role::780',
                        'attributes' => [
                            'name' => 'Role2'
                        ]
                    ]
                ]
            ],
            $this->documentBuilder->getDocument()
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testSetDataCollectionWithMetadata()
    {
        $object = [
            'id'         => 123,
            'name'       => 'Name',
            'meta1'      => 'Meta1',
            'category'   => 456,
            'group'      => null,
            'role'       => ['id' => 789],
            'categories' => [
                ['id' => 456],
                ['id' => 457]
            ],
            'groups'     => null,
            'products'   => [],
            'roles'      => [
                ['id' => 789, 'name' => 'Role1'],
                ['id' => 780, 'name' => 'Role2']
            ],
            'unknown'    => 'test'
        ];

        $metadata = $this->getEntityMetadata('Test\Entity', ['id']);
        $metadata->addField($this->createFieldMetadata('id'));
        $metadata->addField($this->createFieldMetadata('name'));
        $metadata->addField($this->createFieldMetadata('missingField'));
        $metadata->addMetaProperty($this->createMetaPropertyMetadata('meta1'));
        $metadata->addMetaProperty($this->createMetaPropertyMetadata('missingMeta'));
        $metadata->addAssociation($this->createAssociationMetadata('category', 'Test\Category'));
        $metadata->addAssociation($this->createAssociationMetadata('group', 'Test\Groups'));
        $metadata->addAssociation($this->createAssociationMetadata('role', 'Test\Role'));
        $metadata->addAssociation($this->createAssociationMetadata('categories', 'Test\Category', true));
        $metadata->addAssociation($this->createAssociationMetadata('groups', 'Test\Group', true));
        $metadata->addAssociation($this->createAssociationMetadata('products', 'Test\Product', true));
        $metadata->addAssociation($this->createAssociationMetadata('roles', 'Test\Role', true));
        $metadata->getAssociation('roles')->getTargetMetadata()->addField($this->createFieldMetadata('name'));
        $metadata->addAssociation($this->createAssociationMetadata('missingToOne', 'Test\Class'));
        $metadata->addAssociation($this->createAssociationMetadata('missingToMany', 'Test\Class', true));

        $this->documentBuilder->setDataCollection([$object], $this->requestType, $metadata);
        self::assertEquals(
            [
                'data'     => [
                    [
                        'type'          => 'test_entity',
                        'id'            => 'Test\Entity::123',
                        'meta'          => [
                            'meta1' => 'Meta1'
                        ],
                        'attributes'    => [
                            'name'         => 'Name',
                            'missingField' => null
                        ],
                        'relationships' => [
                            'category'      => [
                                'data' => [
                                    'type' => 'test_category',
                                    'id'   => 'Test\Category::456'
                                ]
                            ],
                            'group'         => [
                                'data' => null
                            ],
                            'role'          => [
                                'data' => [
                                    'type' => 'test_role',
                                    'id'   => 'Test\Role::789'
                                ]
                            ],
                            'categories'    => [
                                'data' => [
                                    [
                                        'type' => 'test_category',
                                        'id'   => 'Test\Category::456'
                                    ],
                                    [
                                        'type' => 'test_category',
                                        'id'   => 'Test\Category::457'
                                    ]
                                ]
                            ],
                            'groups'        => [
                                'data' => []
                            ],
                            'products'      => [
                                'data' => []
                            ],
                            'roles'         => [
                                'data' => [
                                    [
                                        'type' => 'test_role',
                                        'id'   => 'Test\Role::789'
                                    ],
                                    [
                                        'type' => 'test_role',
                                        'id'   => 'Test\Role::780'
                                    ]
                                ]
                            ],
                            'missingToOne'  => [
                                'data' => null
                            ],
                            'missingToMany' => [
                                'data' => []
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type'       => 'test_role',
                        'id'         => 'Test\Role::789',
                        'attributes' => [
                            'name' => 'Role1'
                        ]
                    ],
                    [
                        'type'       => 'test_role',
                        'id'         => 'Test\Role::780',
                        'attributes' => [
                            'name' => 'Role2'
                        ]
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
                'data' => [
                    'type'       => 'test_entity',
                    'id'         => 'Test\Entity::123',
                    'links'      => [
                        'self'      => '/api/test_entity/Test\Entity::123',
                        'with_meta' => [
                            'href' => '/api/test_entity/Test\Entity::123/meta',
                            'meta' => [
                                'meta1' => 'Name',
                                'meta3' => 'Test\Entity'
                            ]
                        ]
                    ],
                    'attributes' => [
                        'name' => 'Name'
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
                'data' => [
                    [
                        'type'       => 'test_entity',
                        'id'         => 'Test\Entity::123',
                        'links'      => [
                            'self'      => '/api/test_entity/Test\Entity::123',
                            'with_meta' => [
                                'href' => '/api/test_entity/Test\Entity::123/meta',
                                'meta' => [
                                    'meta1' => 'Name',
                                    'meta3' => 'Test\Entity'
                                ]
                            ]
                        ],
                        'attributes' => [
                            'name' => 'Name'
                        ]
                    ]
                ]
            ],
            $this->documentBuilder->getDocument()
        );
    }

    public function testAssociationWithLinks()
    {
        $object = [
            'code'       => 123,
            'category'   => 456,
            'categories' => [456, 457]
        ];

        $metadata = $this->getEntityMetadata('Test\Entity', ['code']);
        $metadata->addField($this->createFieldMetadata('code'));
        $metadata->addAssociation($this->createAssociationMetadata('category', 'Test\Category'));
        $metadata->addAssociation($this->createAssociationMetadata('categories', 'Test\Category', true));

        foreach ($metadata->getAssociations() as $association) {
            $association->addLink('self', new ExternalLinkMetadata(
                '/api/{entityType}/{entityId}/' . $association->getName() . '/{id}',
                ['entityType' => '_.__type__', 'entityId' => '_.__id__', 'id' => '__id__']
            ));
            $linkWithMeta = $association->addLink('with_meta', new ExternalLinkMetadata(
                '/api/{entityType}/{entityId}/' . $association->getName() . '/{id}/meta',
                ['entityType' => '_.__type__', 'entityId' => '_.__id__', 'id' => '__id__']
            ));
            $linkWithMeta->addMetaProperty(new MetaAttributeMetadata('meta1', 'string', 'name'));
            $linkWithMeta->addMetaProperty(new MetaAttributeMetadata('meta2', 'string'));
        }

        $this->documentBuilder->setDataObject($object, $this->requestType, $metadata);
        self::assertEquals(
            [
                'data' => [
                    'type'          => 'test_entity',
                    'id'            => 'Test\Entity::123',
                    'relationships' => [
                        'category'   => [
                            'data' => [
                                'type'  => 'test_category',
                                'id'    => 'Test\Category::456',
                                'links' => [
                                    'self'      =>
                                        '/api/test_entity/Test\Entity::123/category/Test\Category::456',
                                    'with_meta' =>
                                        '/api/test_entity/Test\Entity::123/category/Test\Category::456/meta'
                                ]
                            ]
                        ],
                        'categories' => [
                            'data' => [
                                [
                                    'type'  => 'test_category',
                                    'id'    => 'Test\Category::456',
                                    'links' => [
                                        'self'      =>
                                            '/api/test_entity/Test\Entity::123/categories/Test\Category::456',
                                        'with_meta' =>
                                            '/api/test_entity/Test\Entity::123/categories/Test\Category::456/meta'
                                    ]
                                ],
                                [
                                    'type'  => 'test_category',
                                    'id'    => 'Test\Category::457',
                                    'links' => [
                                        'self'      =>
                                            '/api/test_entity/Test\Entity::123/categories/Test\Category::457',
                                        'with_meta' =>
                                            '/api/test_entity/Test\Entity::123/categories/Test\Category::457/meta'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $this->documentBuilder->getDocument()
        );
    }

    public function testAssociationWithLinksForRelationship()
    {
        $object = [
            'code'       => 123,
            'category'   => 456,
            'categories' => [456, 457]
        ];

        $metadata = $this->getEntityMetadata('Test\Entity', ['code']);
        $metadata->addField($this->createFieldMetadata('code'));
        $metadata->addAssociation($this->createAssociationMetadata('category', 'Test\Category', false, ['code']));
        $metadata->addAssociation($this->createAssociationMetadata('categories', 'Test\Category', true, ['code']));

        foreach ($metadata->getAssociations() as $association) {
            $association->addRelationshipLink('self', new ExternalLinkMetadata(
                '/api/{entityType}/{entityId}/' . $association->getName(),
                ['entityType' => '_.__type__', 'entityId' => '_.__id__']
            ));
            $linkWithMeta = $association->addRelationshipLink('with_meta', new ExternalLinkMetadata(
                '/api/{entityType}/{entityId}/' . $association->getName() . '/meta',
                ['entityType' => '_.__type__', 'entityId' => '_.__id__']
            ));
            $linkWithMeta->addMetaProperty(new MetaAttributeMetadata('meta1', 'string', 'name'));
            $linkWithMeta->addMetaProperty(new MetaAttributeMetadata('meta2', 'string'));
        }

        $this->documentBuilder->setDataObject($object, $this->requestType, $metadata);
        self::assertEquals(
            [
                'data' => [
                    'type'          => 'test_entity',
                    'id'            => 'Test\Entity::123',
                    'relationships' => [
                        'category'   => [
                            'links' => [
                                'self'      => '/api/test_entity/Test\Entity::123/category',
                                'with_meta' => '/api/test_entity/Test\Entity::123/category/meta'
                            ],
                            'data'  => [
                                'type' => 'test_category',
                                'id'   => 'Test\Category::456'
                            ]
                        ],
                        'categories' => [
                            'links' => [
                                'self'      => '/api/test_entity/Test\Entity::123/categories',
                                'with_meta' => '/api/test_entity/Test\Entity::123/categories/meta'
                            ],
                            'data'  => [
                                [
                                    'type' => 'test_category',
                                    'id'   => 'Test\Category::456'
                                ],
                                [
                                    'type' => 'test_category',
                                    'id'   => 'Test\Category::457'
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $this->documentBuilder->getDocument()
        );
    }

    public function testAssociationWithMetaProperties()
    {
        $object = [
            'code'       => 123,
            'category'   => ['code' => 456, '_meta1' => 'category_meta1'],
            'categories' => [
                ['code' => 456, '_meta1' => 'category_meta1_item1'],
                ['code' => 457, '_meta1' => 'category_meta1_item2']
            ]
        ];

        $metadata = $this->getEntityMetadata('Test\Entity', ['code']);
        $metadata->addField($this->createFieldMetadata('code'));
        $metadata->addAssociation($this->createAssociationMetadata('category', 'Test\Category', false, ['code']));
        $metadata->addAssociation($this->createAssociationMetadata('categories', 'Test\Category', true, ['code']));

        foreach ($metadata->getAssociations() as $association) {
            $association->addMetaProperty(new MetaAttributeMetadata('meta1', 'string', '_meta1'));
            $association->addMetaProperty(new MetaAttributeMetadata('meta2', 'string'));
        }

        $this->documentBuilder->setDataObject($object, $this->requestType, $metadata);
        self::assertEquals(
            [
                'data' => [
                    'type'          => 'test_entity',
                    'id'            => 'Test\Entity::123',
                    'relationships' => [
                        'category'   => [
                            'data' => [
                                'type' => 'test_category',
                                'id'   => 'Test\Category::456',
                                'meta' => [
                                    'meta1' => 'category_meta1'
                                ]
                            ]
                        ],
                        'categories' => [
                            'data' => [
                                [
                                    'type' => 'test_category',
                                    'id'   => 'Test\Category::456',
                                    'meta' => [
                                        'meta1' => 'category_meta1_item1'
                                    ]
                                ],
                                [
                                    'type' => 'test_category',
                                    'id'   => 'Test\Category::457',
                                    'meta' => [
                                        'meta1' => 'category_meta1_item2'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $this->documentBuilder->getDocument()
        );
    }

    public function testAssociationWithMetaPropertiesForRelationship()
    {
        $object = [
            'code'              => 123,
            'category'          => 456,
            'categories'        => [456, 457],
            '_category_meta1'   => 'category_meta1',
            '_categories_meta1' => 'categories_meta1'
        ];

        $metadata = $this->getEntityMetadata('Test\Entity', ['code']);
        $metadata->addField($this->createFieldMetadata('code'));
        $metadata->addAssociation($this->createAssociationMetadata('category', 'Test\Category'));
        $metadata->addAssociation($this->createAssociationMetadata('categories', 'Test\Category', true));

        foreach ($metadata->getAssociations() as $association) {
            $association->addRelationshipMetaProperty(
                new MetaAttributeMetadata('meta1', 'string', '_._' . $association->getName() . '_meta1')
            );
            $association->addRelationshipMetaProperty(new MetaAttributeMetadata('meta2', 'string'));
        }

        $this->documentBuilder->setDataObject($object, $this->requestType, $metadata);
        self::assertEquals(
            [
                'data' => [
                    'type'          => 'test_entity',
                    'id'            => 'Test\Entity::123',
                    'relationships' => [
                        'category'   => [
                            'meta' => [
                                'meta1' => 'category_meta1'
                            ],
                            'data' => [
                                'type' => 'test_category',
                                'id'   => 'Test\Category::456'
                            ]
                        ],
                        'categories' => [
                            'meta' => [
                                'meta1' => 'categories_meta1'
                            ],
                            'data' => [
                                [
                                    'type' => 'test_category',
                                    'id'   => 'Test\Category::456'
                                ],
                                [
                                    'type' => 'test_category',
                                    'id'   => 'Test\Category::457'
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $this->documentBuilder->getDocument()
        );
    }

    public function testAssociationWithInheritance()
    {
        $object = [
            'id'         => 123,
            'categories' => [
                ['id' => 456, '__class__' => 'Test\Category1', 'name' => 'Category1'],
                ['id' => 457, '__class__' => 'Test\Category2', 'name' => 'Category2']
            ]
        ];

        $metadata = $this->getEntityMetadata('Test\Entity', ['id']);
        $metadata->addField($this->createFieldMetadata('id'));
        $metadata->addAssociation($this->createAssociationMetadata('categories', 'Test\CategoryWithoutAlias', true));
        $metadata->getAssociation('categories')->getTargetMetadata()->setInheritedType(true);
        $metadata->getAssociation('categories')->setAcceptableTargetClassNames(
            ['Test\Category1', 'Test\Category2']
        );
        $metadata->getAssociation('categories')->getTargetMetadata()->addField($this->createFieldMetadata('name'));

        $this->documentBuilder->setDataObject($object, $this->requestType, $metadata);
        self::assertEquals(
            [
                'data'     => [
                    'type'          => 'test_entity',
                    'id'            => 'Test\Entity::123',
                    'relationships' => [
                        'categories' => [
                            'data' => [
                                [
                                    'type' => 'test_category1',
                                    'id'   => 'Test\CategoryWithoutAlias::456'
                                ],
                                [
                                    'type' => 'test_category2',
                                    'id'   => 'Test\CategoryWithoutAlias::457'
                                ]
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type'       => 'test_category1',
                        'id'         => 'Test\CategoryWithoutAlias::456',
                        'attributes' => [
                            'name' => 'Category1'
                        ]
                    ],
                    [
                        'type'       => 'test_category2',
                        'id'         => 'Test\CategoryWithoutAlias::457',
                        'attributes' => [
                            'name' => 'Category2'
                        ]
                    ]
                ]
            ],
            $this->documentBuilder->getDocument()
        );
    }

    public function testAssociationWithInheritanceAndSomeInheritedEntitiesDoNotHaveAlias()
    {
        $object = [
            'id'         => 123,
            'categories' => [
                ['id' => 456, '__class__' => 'Test\Category1', 'name' => 'Category1'],
                ['id' => 457, '__class__' => 'Test\Category2WithoutAlias', 'name' => 'Category2']
            ]
        ];

        $metadata = $this->getEntityMetadata('Test\Entity', ['id']);
        $metadata->addField($this->createFieldMetadata('id'));
        $metadata->addAssociation($this->createAssociationMetadata('categories', 'Test\Category', true));
        $metadata->getAssociation('categories')->getTargetMetadata()->setInheritedType(true);
        $metadata->getAssociation('categories')->setAcceptableTargetClassNames(
            ['Test\Category1', 'Test\Category2WithoutAlias']
        );
        $metadata->getAssociation('categories')->getTargetMetadata()->addField($this->createFieldMetadata('name'));

        $this->documentBuilder->setDataObject($object, $this->requestType, $metadata);
        self::assertEquals(
            [
                'data'     => [
                    'type'          => 'test_entity',
                    'id'            => 'Test\Entity::123',
                    'relationships' => [
                        'categories' => [
                            'data' => [
                                [
                                    'type' => 'test_category1',
                                    'id'   => 'Test\Category::456'
                                ],
                                [
                                    'type' => 'test_category',
                                    'id'   => 'Test\Category::457'
                                ]
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type'       => 'test_category1',
                        'id'         => 'Test\Category::456',
                        'attributes' => [
                            'name' => 'Category1'
                        ]
                    ],
                    [
                        'type'       => 'test_category',
                        'id'         => 'Test\Category::457',
                        'attributes' => [
                            'name' => 'Category2'
                        ]
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
                'data' => [
                    'type'       => 'test_entity',
                    'id'         => 'Test\Entity::123',
                    'attributes' => [
                        'missingToOne'  => null,
                        'missingToMany' => []
                    ]
                ]
            ],
            $this->documentBuilder->getDocument()
        );
    }

    /**
     * @dataProvider toOneAssociationAsFieldProvider
     */
    public function testToOneAssociationAsField($value, $expected)
    {
        $object = [
            'id'       => 123,
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
                'data' => [
                    'type'       => 'test_entity',
                    'id'         => 'Test\Entity::123',
                    'attributes' => [
                        'category' => $expected
                    ]
                ]
            ],
            $this->documentBuilder->getDocument()
        );
    }

    public function toOneAssociationAsFieldProvider()
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
    public function testToManyAssociationAsField($value, $expected)
    {
        $object = [
            'id'         => 123,
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
                'data' => [
                    'type'       => 'test_entity',
                    'id'         => 'Test\Entity::123',
                    'attributes' => [
                        'categories' => $expected
                    ]
                ]
            ],
            $this->documentBuilder->getDocument()
        );
    }

    public function toManyAssociationAsFieldProvider()
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
    public function testToOneAssociationAsFieldForIdFieldsOnly($value, $expected)
    {
        $object = [
            'id'       => 123,
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
                'data' => [
                    'type'       => 'test_entity',
                    'id'         => 'Test\Entity::123',
                    'attributes' => [
                        'category' => $expected
                    ]
                ]
            ],
            $this->documentBuilder->getDocument()
        );
    }

    public function toOneAssociationAsFieldForIdFieldsOnlyProvider()
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
    public function testToManyAssociationAsFieldForIdFieldsOnly($value, $expected)
    {
        $object = [
            'id'         => 123,
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
                'data' => [
                    'type'       => 'test_entity',
                    'id'         => 'Test\Entity::123',
                    'attributes' => [
                        'categories' => $expected
                    ]
                ]
            ],
            $this->documentBuilder->getDocument()
        );
    }

    public function toManyAssociationAsFieldForIdFieldsOnlyProvider()
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
    public function testToOneCollapsedAssociationAsField($value, $expected)
    {
        $object = [
            'id'       => 123,
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
                'data' => [
                    'type'       => 'test_entity',
                    'id'         => 'Test\Entity::123',
                    'attributes' => [
                        'category' => $expected
                    ]
                ]
            ],
            $this->documentBuilder->getDocument()
        );
    }

    public function toOneCollapsedAssociationAsFieldProvider()
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
    public function testToManyCollapsedAssociationAsField($value, $expected)
    {
        $object = [
            'id'         => 123,
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
                'data' => [
                    'type'       => 'test_entity',
                    'id'         => 'Test\Entity::123',
                    'attributes' => [
                        'categories' => $expected
                    ]
                ]
            ],
            $this->documentBuilder->getDocument()
        );
    }

    public function toManyCollapsedAssociationAsFieldProvider()
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
            'id'          => 1,
            'association' => [
                'id'         => 123,
                'name'       => 'Name',
                'meta1'      => 'Meta1',
                'meta2'      => 'Meta2',
                'category'   => 456,
                'group'      => null,
                'role'       => ['id' => 789],
                'categories' => [
                    ['id' => 456],
                    ['id' => 457]
                ],
                'groups'     => null,
                'products'   => [],
                'roles'      => [
                    ['id' => 789, 'name' => 'Role1'],
                    ['id' => 780, 'name' => 'Role2']
                ],
                'unknown'    => 'test'
            ]
        ];

        $targetMetadata = $this->getEntityMetadata('Test\Target', ['id']);
        $targetMetadata->addField($this->createFieldMetadata('id'));
        $targetMetadata->addField($this->createFieldMetadata('name'));
        $targetMetadata->addField($this->createFieldMetadata('missingField'));
        $targetMetadata->addMetaProperty($this->createMetaPropertyMetadata('meta1'));
        $targetMetadata->addMetaProperty($this->createMetaPropertyMetadata('meta2'))
            ->setResultName('resultMeta2');
        $targetMetadata->addMetaProperty($this->createMetaPropertyMetadata('missingMeta'));
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
        $metadata->addField($this->createFieldMetadata('id'));
        $associationMetadata = $metadata->addAssociation(
            $this->createAssociationMetadata('association', 'Test\Target')
        );
        $associationMetadata->setTargetMetadata($targetMetadata);
        $associationMetadata->setDataType('array');

        $this->documentBuilder->setDataObject($object, $this->requestType, $metadata);
        self::assertEquals(
            [
                'data' => [
                    'type'       => 'test_entity',
                    'id'         => 'Test\Entity::1',
                    'attributes' => [
                        'association' => [
                            'id'            => 123,
                            'name'          => 'Name',
                            'missingField'  => null,
                            'meta1'         => 'Meta1',
                            'resultMeta2'   => 'Meta2',
                            'category'      => 456,
                            'group'         => null,
                            'role'          => 789,
                            'categories'    => [456, 457],
                            'groups'        => [],
                            'products'      => [],
                            'roles'         => [
                                ['id' => 789, 'name' => 'Role1'],
                                ['id' => 780, 'name' => 'Role2']
                            ],
                            'missingToOne'  => null,
                            'missingToMany' => []
                        ]
                    ]
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
                'errors' => [
                    [
                        'status' => '500',
                        'code'   => 'errCode',
                        'title'  => 'some error',
                        'detail' => 'some error details'
                    ]
                ]
            ],
            $this->documentBuilder->getDocument()
        );
    }

    public function testSetErrorCollection()
    {
        $error = new Error();
        $error->setStatusCode(500);
        $error->setCode('errCode');
        $error->setTitle('some error');
        $error->setDetail('some error details');

        $this->documentBuilder->setErrorCollection([$error]);
        self::assertEquals(
            [
                'errors' => [
                    [
                        'status' => '500',
                        'code'   => 'errCode',
                        'title'  => 'some error',
                        'detail' => 'some error details'
                    ]
                ]
            ],
            $this->documentBuilder->getDocument()
        );
    }

    public function testMetaPropertyWithResultName()
    {
        $object = [
            'id'    => 123,
            'meta1' => 'Meta1'
        ];

        $metadata = $this->getEntityMetadata('Test\Entity', ['id']);
        $metadata->addField($this->createFieldMetadata('id'));
        $metadata->addMetaProperty($this->createMetaPropertyMetadata('meta1'))
            ->setResultName('resultMeta1');

        $this->documentBuilder->setDataObject($object, $this->requestType, $metadata);
        self::assertEquals(
            [
                'data' => [
                    'type' => 'test_entity',
                    'id'   => 'Test\Entity::123',
                    'meta' => [
                        'resultMeta1' => 'Meta1'
                    ]
                ]
            ],
            $this->documentBuilder->getDocument()
        );
    }

    public function testSetDataObjectForEntityWithoutIdentifier()
    {
        $object = [
            'name'       => 'Name',
            'meta1'      => 'Meta1',
            'category'   => 456,
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
                'meta' => [
                    'name'       => 'Name',
                    'meta1'      => 'Meta1',
                    'category'   => ['type' => 'test_category', 'id' => 'Test\Category::456'],
                    'categories' => [
                        ['type' => 'test_category', 'id' => 'Test\Category::456'],
                        ['type' => 'test_category', 'id' => 'Test\Category::457']
                    ]
                ]
            ],
            $this->documentBuilder->getDocument()
        );
    }

    public function testInputOnlyMetaProperty()
    {
        $object = [
            'id'    => 123,
            'meta1' => 'Meta1'
        ];

        $metadata = $this->getEntityMetadata('Test\Entity', ['id']);
        $metadata->addField($this->createFieldMetadata('id'));
        $metadata->addMetaProperty($this->createMetaPropertyMetadata('meta1'))
            ->setDirection(true, false);

        $this->documentBuilder->setDataObject($object, $this->requestType, $metadata);
        self::assertEquals(
            [
                'data' => [
                    'type' => 'test_entity',
                    'id'   => 'Test\Entity::123'
                ]
            ],
            $this->documentBuilder->getDocument()
        );
    }

    public function testInputOnlyField()
    {
        $object = [
            'id'     => 123,
            'field1' => 'value1'
        ];

        $metadata = $this->getEntityMetadata('Test\Entity', ['id']);
        $metadata->addField($this->createFieldMetadata('id'));
        $metadata->addField($this->createFieldMetadata('field1'))
            ->setDirection(true, false);

        $this->documentBuilder->setDataObject($object, $this->requestType, $metadata);
        self::assertEquals(
            [
                'data' => [
                    'type' => 'test_entity',
                    'id'   => 'Test\Entity::123'
                ]
            ],
            $this->documentBuilder->getDocument()
        );
    }

    public function testInputOnlyAssociation()
    {
        $object = [
            'id'           => 123,
            'association1' => 456
        ];

        $metadata = $this->getEntityMetadata('Test\Entity', ['id']);
        $metadata->addField($this->createFieldMetadata('id'));
        $metadata->addAssociation($this->createAssociationMetadata('association1', 'Test\Category'))
            ->setDirection(true, false);

        $this->documentBuilder->setDataObject($object, $this->requestType, $metadata);
        self::assertEquals(
            [
                'data' => [
                    'type' => 'test_entity',
                    'id'   => 'Test\Entity::123'
                ]
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
            [
                'data' => []
            ],
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
            [
                'data' => []
            ],
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
            [
                'data' => []
            ],
            $this->documentBuilder->getDocument()
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testAddLinksToCollectionValuedResult()
    {
        $object = [
            'id'    => 1,
            'role'  => ['id' => 21, 'name' => 'Role1', 'users' => [211, 212]],
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
                'links'    => [
                    'link10' => '/api/test',
                    'link1'  => 'link1_url',
                    'link2'  => ['href' => 'link2_url', 'meta' => ['key' => 'value']]
                ],
                'data'     => [
                    [
                        'type'          => 'test_entity',
                        'id'            => 'Test\Entity::1',
                        'relationships' => [
                            'role'  => [
                                'data' => ['type' => 'test_role', 'id' => 'Test\Role::21']
                            ],
                            'roles' => [
                                'data' => [
                                    ['type' => 'test_role', 'id' => 'Test\Role::21'],
                                    ['type' => 'test_role', 'id' => 'Test\Role::22']
                                ]
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type'          => 'test_role',
                        'id'            => 'Test\Role::21',
                        'attributes'    => ['name' => 'Role1'],
                        'relationships' => [
                            'users' => [
                                'data' => [
                                    ['type' => 'test_user', 'id' => 'Test\User::211'],
                                    ['type' => 'test_user', 'id' => 'Test\User::212']
                                ]
                            ]
                        ]
                    ],
                    [
                        'type'          => 'test_role',
                        'id'            => 'Test\Role::22',
                        'attributes'    => ['name' => 'Role2'],
                        'relationships' => [
                            'users' => [
                                'data' => [
                                    ['type' => 'test_user', 'id' => 'Test\User::213'],
                                    ['type' => 'test_user', 'id' => 'Test\User::214']
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $this->documentBuilder->getDocument()
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testAddLinksToSingleValuedResult()
    {
        $object = [
            'id'    => 1,
            'role'  => ['id' => 21, 'name' => 'Role1', 'users' => [211, 212]],
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
                'links'    => [
                    'link10' => '/api/test',
                    'link1'  => 'link1_url',
                    'link2'  => ['href' => 'link2_url', 'meta' => ['key' => 'value']]
                ],
                'data'     => [
                    'type'          => 'test_entity',
                    'id'            => 'Test\Entity::1',
                    'relationships' => [
                        'role'  => [
                            'data' => ['type' => 'test_role', 'id' => 'Test\Role::21']
                        ],
                        'roles' => [
                            'data' => [
                                ['type' => 'test_role', 'id' => 'Test\Role::21'],
                                ['type' => 'test_role', 'id' => 'Test\Role::22']
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type'          => 'test_role',
                        'id'            => 'Test\Role::21',
                        'attributes'    => ['name' => 'Role1'],
                        'relationships' => [
                            'users' => [
                                'data' => [
                                    ['type' => 'test_user', 'id' => 'Test\User::211'],
                                    ['type' => 'test_user', 'id' => 'Test\User::212']
                                ]
                            ]
                        ]
                    ],
                    [
                        'type'          => 'test_role',
                        'id'            => 'Test\Role::22',
                        'attributes'    => ['name' => 'Role2'],
                        'relationships' => [
                            'users' => [
                                'data' => [
                                    ['type' => 'test_user', 'id' => 'Test\User::213'],
                                    ['type' => 'test_user', 'id' => 'Test\User::214']
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $this->documentBuilder->getDocument()
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testSetDataObjectWithPredefinedMetaProperties()
    {
        $object = [
            'id'         => 1,
            'category'   => 11,
            'categories' => [
                ['id' => 11],
                ['id' => 12]
            ],
            'role'       => ['id' => 21, 'name' => 'Role1', 'users' => [211, 212]],
            'roles'      => [
                ['id' => 21, 'name' => 'Role1', 'users' => [211, 212]],
                ['id' => 22, 'name' => 'Role2', '__class__' => 'Test\AnotherRole', 'users' => [213, 214]]
            ]
        ];

        $metadata = $this->getEntityMetadata('Test\Entity', ['id']);
        $metadata->addField($this->createFieldMetadata('id'));
        $this->addEntityPredefinedMetaProperties($metadata);
        $this->addAssociationPredefinedMetaProperties(
            $metadata->addAssociation($this->createAssociationMetadata('category', 'Test\Category'))
        );
        $this->addAssociationPredefinedMetaProperties(
            $metadata->addAssociation($this->createAssociationMetadata('categories', 'Test\Category', true))
        );
        $this->addAssociationPredefinedMetaProperties(
            $metadata->addAssociation($this->createAssociationMetadata('role', 'Test\Role'))
        );
        $this->addAssociationPredefinedMetaProperties(
            $metadata->addAssociation($this->createAssociationMetadata('roles', 'Test\Role', true))
        );
        $roleMetadata = $this->getEntityMetadata('Test\Role', ['id']);
        $roleMetadata->setInheritedType(true);
        $roleMetadata->addField($this->createFieldMetadata('name'));
        $this->addEntityPredefinedMetaProperties($roleMetadata);
        $this->addAssociationPredefinedMetaProperties(
            $roleMetadata->addAssociation($this->createAssociationMetadata('users', 'Test\User', true))
        );
        $metadata->getAssociation('role')->setTargetMetadata($roleMetadata);
        $metadata->getAssociation('roles')->setTargetMetadata($roleMetadata);

        $this->documentBuilder->setMetadata([
            'categories'    => ['has_more' => true],
            'roles.1.users' => ['has_more' => true]
        ]);
        $this->documentBuilder->setDataObject($object, $this->requestType, $metadata);

        self::assertEquals(
            [
                'data'     => [
                    'meta'          => [
                        '__path__'  => '',
                        '__class__' => 'Test\Entity',
                        '__type__'  => 'test_entity',
                        '__id__'    => 'Test\Entity::1'
                    ],
                    'type'          => 'test_entity',
                    'id'            => 'Test\Entity::1',
                    'relationships' => [
                        'category'   => [
                            'meta' => [
                                '__path__'  => 'category',
                                '__class__' => 'Test\Category',
                                '__type__'  => 'test_category'
                            ],
                            'data' => [
                                'meta' => [
                                    '__path__'  => 'category',
                                    '__class__' => 'Test\Category',
                                    '__type__'  => 'test_category',
                                    '__id__'    => 'Test\Category::11'
                                ],
                                'type' => 'test_category',
                                'id'   => 'Test\Category::11'
                            ]
                        ],
                        'categories' => [
                            'meta' => [
                                '__path__'     => 'categories',
                                '__class__'    => 'Test\Category',
                                '__type__'     => 'test_category',
                                '__has_more__' => true
                            ],
                            'data' => [
                                [
                                    'meta' => [
                                        '__path__'  => 'categories.0',
                                        '__class__' => 'Test\Category',
                                        '__type__'  => 'test_category',
                                        '__id__'    => 'Test\Category::11'
                                    ],
                                    'type' => 'test_category',
                                    'id'   => 'Test\Category::11'
                                ],
                                [
                                    'meta' => [
                                        '__path__'  => 'categories.1',
                                        '__class__' => 'Test\Category',
                                        '__type__'  => 'test_category',
                                        '__id__'    => 'Test\Category::12'
                                    ],
                                    'type' => 'test_category',
                                    'id'   => 'Test\Category::12'
                                ]
                            ]
                        ],
                        'role'       => [
                            'meta' => [
                                '__path__'  => 'role',
                                '__class__' => 'Test\Role',
                                '__type__'  => 'test_role'
                            ],
                            'data' => [
                                'meta' => [
                                    '__path__'  => 'role',
                                    '__class__' => 'Test\Role',
                                    '__type__'  => 'test_role',
                                    '__id__'    => 'Test\Role::21'
                                ],
                                'type' => 'test_role',
                                'id'   => 'Test\Role::21'
                            ]
                        ],
                        'roles'      => [
                            'meta' => [
                                '__path__'  => 'roles',
                                '__class__' => 'Test\Role',
                                '__type__'  => 'test_role'
                            ],
                            'data' => [
                                [
                                    'meta' => [
                                        '__path__'  => 'roles.0',
                                        '__class__' => 'Test\Role',
                                        '__type__'  => 'test_role',
                                        '__id__'    => 'Test\Role::21'
                                    ],
                                    'type' => 'test_role',
                                    'id'   => 'Test\Role::21'
                                ],
                                [
                                    'meta' => [
                                        '__path__'  => 'roles.1',
                                        '__class__' => 'Test\AnotherRole',
                                        '__type__'  => 'test_anotherrole',
                                        '__id__'    => 'Test\Role::22'
                                    ],
                                    'type' => 'test_anotherrole',
                                    'id'   => 'Test\Role::22'
                                ]
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'meta'          => [
                            '__path__'  => 'role',
                            '__class__' => 'Test\Role',
                            '__type__'  => 'test_role',
                            '__id__'    => 'Test\Role::21'
                        ],
                        'type'          => 'test_role',
                        'id'            => 'Test\Role::21',
                        'attributes'    => ['name' => 'Role1'],
                        'relationships' => [
                            'users' => [
                                'meta' => [
                                    '__path__'  => 'role.users',
                                    '__class__' => 'Test\User',
                                    '__type__'  => 'test_user'
                                ],
                                'data' => [
                                    [
                                        'meta' => [
                                            '__path__'  => 'role.users.0',
                                            '__class__' => 'Test\User',
                                            '__type__'  => 'test_user',
                                            '__id__'    => 'Test\User::211'
                                        ],
                                        'type' => 'test_user',
                                        'id'   => 'Test\User::211'
                                    ],
                                    [
                                        'meta' => [
                                            '__path__'  => 'role.users.1',
                                            '__class__' => 'Test\User',
                                            '__type__'  => 'test_user',
                                            '__id__'    => 'Test\User::212'
                                        ],
                                        'type' => 'test_user',
                                        'id'   => 'Test\User::212'
                                    ]
                                ]
                            ]
                        ]
                    ],
                    [
                        'meta'          => [
                            '__path__'  => 'roles.1',
                            '__class__' => 'Test\AnotherRole',
                            '__type__'  => 'test_anotherrole',
                            '__id__'    => 'Test\Role::22'
                        ],
                        'type'          => 'test_anotherrole',
                        'id'            => 'Test\Role::22',
                        'attributes'    => ['name' => 'Role2'],
                        'relationships' => [
                            'users' => [
                                'meta' => [
                                    '__path__'     => 'roles.1.users',
                                    '__class__'    => 'Test\User',
                                    '__type__'     => 'test_user',
                                    '__has_more__' => true
                                ],
                                'data' => [
                                    [
                                        'meta' => [
                                            '__path__'  => 'roles.1.users.0',
                                            '__class__' => 'Test\User',
                                            '__type__'  => 'test_user',
                                            '__id__'    => 'Test\User::213'
                                        ],
                                        'type' => 'test_user',
                                        'id'   => 'Test\User::213'
                                    ],
                                    [
                                        'meta' => [
                                            '__path__'  => 'roles.1.users.1',
                                            '__class__' => 'Test\User',
                                            '__type__'  => 'test_user',
                                            '__id__'    => 'Test\User::214'
                                        ],
                                        'type' => 'test_user',
                                        'id'   => 'Test\User::214'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $this->documentBuilder->getDocument()
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testSetDataCollectionWithPredefinedMetaProperties()
    {
        $object = [
            'id'         => 1,
            'category'   => 11,
            'categories' => [
                ['id' => 11],
                ['id' => 12]
            ],
            'role'       => ['id' => 21, 'name' => 'Role1', 'users' => [211, 212]],
            'roles'      => [
                ['id' => 21, 'name' => 'Role1', 'users' => [211, 212]],
                ['id' => 22, 'name' => 'Role2', '__class__' => 'Test\AnotherRole', 'users' => [213, 214]]
            ]
        ];

        $metadata = $this->getEntityMetadata('Test\Entity', ['id']);
        $metadata->addField($this->createFieldMetadata('id'));
        $this->addEntityPredefinedMetaProperties($metadata);
        $this->addAssociationPredefinedMetaProperties(
            $metadata->addAssociation($this->createAssociationMetadata('category', 'Test\Category'))
        );
        $this->addAssociationPredefinedMetaProperties(
            $metadata->addAssociation($this->createAssociationMetadata('categories', 'Test\Category', true))
        );
        $this->addAssociationPredefinedMetaProperties(
            $metadata->addAssociation($this->createAssociationMetadata('role', 'Test\Role'))
        );
        $this->addAssociationPredefinedMetaProperties(
            $metadata->addAssociation($this->createAssociationMetadata('roles', 'Test\Role', true))
        );
        $roleMetadata = $this->getEntityMetadata('Test\Role', ['id']);
        $roleMetadata->setInheritedType(true);
        $roleMetadata->addField($this->createFieldMetadata('name'));
        $this->addEntityPredefinedMetaProperties($roleMetadata);
        $this->addAssociationPredefinedMetaProperties(
            $roleMetadata->addAssociation($this->createAssociationMetadata('users', 'Test\User', true))
        );
        $metadata->getAssociation('role')->setTargetMetadata($roleMetadata);
        $metadata->getAssociation('roles')->setTargetMetadata($roleMetadata);

        $this->documentBuilder->setMetadata([
            '0.categories'    => ['has_more' => true],
            '0.roles.1.users' => ['has_more' => true]
        ]);
        $this->documentBuilder->setDataCollection([$object], $this->requestType, $metadata);

        self::assertEquals(
            [
                'data'     => [
                    [
                        'meta'          => [
                            '__path__'  => '0',
                            '__class__' => 'Test\Entity',
                            '__type__'  => 'test_entity',
                            '__id__'    => 'Test\Entity::1'
                        ],
                        'type'          => 'test_entity',
                        'id'            => 'Test\Entity::1',
                        'relationships' => [
                            'category'   => [
                                'meta' => [
                                    '__path__'  => '0.category',
                                    '__class__' => 'Test\Category',
                                    '__type__'  => 'test_category'
                                ],
                                'data' => [
                                    'meta' => [
                                        '__path__'  => '0.category',
                                        '__class__' => 'Test\Category',
                                        '__type__'  => 'test_category',
                                        '__id__'    => 'Test\Category::11'
                                    ],
                                    'type' => 'test_category',
                                    'id'   => 'Test\Category::11'
                                ]
                            ],
                            'categories' => [
                                'meta' => [
                                    '__path__'     => '0.categories',
                                    '__class__'    => 'Test\Category',
                                    '__type__'     => 'test_category',
                                    '__has_more__' => true
                                ],
                                'data' => [
                                    [
                                        'meta' => [
                                            '__path__'  => '0.categories.0',
                                            '__class__' => 'Test\Category',
                                            '__type__'  => 'test_category',
                                            '__id__'    => 'Test\Category::11'
                                        ],
                                        'type' => 'test_category',
                                        'id'   => 'Test\Category::11'
                                    ],
                                    [
                                        'meta' => [
                                            '__path__'  => '0.categories.1',
                                            '__class__' => 'Test\Category',
                                            '__type__'  => 'test_category',
                                            '__id__'    => 'Test\Category::12'
                                        ],
                                        'type' => 'test_category',
                                        'id'   => 'Test\Category::12'
                                    ]
                                ]
                            ],
                            'role'       => [
                                'meta' => [
                                    '__path__'  => '0.role',
                                    '__class__' => 'Test\Role',
                                    '__type__'  => 'test_role'
                                ],
                                'data' => [
                                    'meta' => [
                                        '__path__'  => '0.role',
                                        '__class__' => 'Test\Role',
                                        '__type__'  => 'test_role',
                                        '__id__'    => 'Test\Role::21'
                                    ],
                                    'type' => 'test_role',
                                    'id'   => 'Test\Role::21'
                                ]
                            ],
                            'roles'      => [
                                'meta' => [
                                    '__path__'  => '0.roles',
                                    '__class__' => 'Test\Role',
                                    '__type__'  => 'test_role'
                                ],
                                'data' => [
                                    [
                                        'meta' => [
                                            '__path__'  => '0.roles.0',
                                            '__class__' => 'Test\Role',
                                            '__type__'  => 'test_role',
                                            '__id__'    => 'Test\Role::21'
                                        ],
                                        'type' => 'test_role',
                                        'id'   => 'Test\Role::21'
                                    ],
                                    [
                                        'meta' => [
                                            '__path__'  => '0.roles.1',
                                            '__class__' => 'Test\AnotherRole',
                                            '__type__'  => 'test_anotherrole',
                                            '__id__'    => 'Test\Role::22'
                                        ],
                                        'type' => 'test_anotherrole',
                                        'id'   => 'Test\Role::22'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'meta'          => [
                            '__path__'  => '0.role',
                            '__class__' => 'Test\Role',
                            '__type__'  => 'test_role',
                            '__id__'    => 'Test\Role::21'
                        ],
                        'type'          => 'test_role',
                        'id'            => 'Test\Role::21',
                        'attributes'    => ['name' => 'Role1'],
                        'relationships' => [
                            'users' => [
                                'meta' => [
                                    '__path__'  => '0.role.users',
                                    '__class__' => 'Test\User',
                                    '__type__'  => 'test_user'
                                ],
                                'data' => [
                                    [
                                        'meta' => [
                                            '__path__'  => '0.role.users.0',
                                            '__class__' => 'Test\User',
                                            '__type__'  => 'test_user',
                                            '__id__'    => 'Test\User::211'
                                        ],
                                        'type' => 'test_user',
                                        'id'   => 'Test\User::211'
                                    ],
                                    [
                                        'meta' => [
                                            '__path__'  => '0.role.users.1',
                                            '__class__' => 'Test\User',
                                            '__type__'  => 'test_user',
                                            '__id__'    => 'Test\User::212'
                                        ],
                                        'type' => 'test_user',
                                        'id'   => 'Test\User::212'
                                    ]
                                ]
                            ]
                        ]
                    ],
                    [
                        'meta'          => [
                            '__path__'  => '0.roles.1',
                            '__class__' => 'Test\AnotherRole',
                            '__type__'  => 'test_anotherrole',
                            '__id__'    => 'Test\Role::22'
                        ],
                        'type'          => 'test_anotherrole',
                        'id'            => 'Test\Role::22',
                        'attributes'    => ['name' => 'Role2'],
                        'relationships' => [
                            'users' => [
                                'meta' => [
                                    '__path__'     => '0.roles.1.users',
                                    '__class__'    => 'Test\User',
                                    '__type__'     => 'test_user',
                                    '__has_more__' => true
                                ],
                                'data' => [
                                    [
                                        'meta' => [
                                            '__path__'  => '0.roles.1.users.0',
                                            '__class__' => 'Test\User',
                                            '__type__'  => 'test_user',
                                            '__id__'    => 'Test\User::213'
                                        ],
                                        'type' => 'test_user',
                                        'id'   => 'Test\User::213'
                                    ],
                                    [
                                        'meta' => [
                                            '__path__'  => '0.roles.1.users.1',
                                            '__class__' => 'Test\User',
                                            '__type__'  => 'test_user',
                                            '__id__'    => 'Test\User::214'
                                        ],
                                        'type' => 'test_user',
                                        'id'   => 'Test\User::214'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $this->documentBuilder->getDocument()
        );
    }
}
