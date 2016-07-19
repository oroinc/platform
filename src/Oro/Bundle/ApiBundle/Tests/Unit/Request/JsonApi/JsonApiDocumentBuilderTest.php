<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Request\JsonApi;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Tests\Unit\Request\DocumentBuilderTestCase;
use Oro\Bundle\EntityBundle\Exception\EntityAliasNotFoundException;

class JsonApiDocumentBuilderTest extends DocumentBuilderTestCase
{
    /** @var JsonApiDocumentBuilder */
    protected $documentBuilder;

    protected function setUp()
    {
        $valueNormalizer = $this->getMockBuilder('Oro\Bundle\ApiBundle\Request\ValueNormalizer')
            ->disableOriginalConstructor()
            ->getMock();
        $valueNormalizer->expects($this->any())
            ->method('normalizeValue')
            ->willReturnCallback(
                function ($value, $dataType, $requestType, $isArrayAllowed) {
                    $this->assertEquals(DataType::ENTITY_TYPE, $dataType);
                    $this->assertEquals(new RequestType([RequestType::JSON_API]), $requestType);
                    $this->assertFalse($isArrayAllowed);

                    if (false !== strpos($value, 'WithoutAlias')) {
                        throw new EntityAliasNotFoundException();
                    }

                    return strtolower(str_replace('\\', '_', $value));
                }
            );

        $entityIdTransformer = $this->getMock('Oro\Bundle\ApiBundle\Request\EntityIdTransformerInterface');
        $entityIdTransformer->expects($this->any())
            ->method('transform')
            ->willReturnCallback(
                function ($id) {
                    return (string)$id;
                }
            );

        $this->documentBuilder = new JsonApiDocumentBuilder(
            $valueNormalizer,
            $entityIdTransformer
        );
    }

    public function testSetDataObjectWithoutMetadata()
    {
        $object = [
            'id'   => 123,
            'name' => 'Name',
        ];

        $this->documentBuilder->setDataObject($object);
        $this->assertEquals(
            [
                'data' => [
                    'attributes' => [
                        'id'   => 123,
                        'name' => 'Name',
                    ]
                ]
            ],
            $this->documentBuilder->getDocument()
        );
    }

    public function testSetDataCollectionWithoutMetadata()
    {
        $object = [
            'id'   => 123,
            'name' => 'Name',
        ];

        $this->documentBuilder->setDataCollection([$object]);
        $this->assertEquals(
            [
                'data' => [
                    [
                        'attributes' => [
                            'id'   => 123,
                            'name' => 'Name',
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
        $metadata->addMetaProperty($this->createMetaPropertyMetadata('meta1'));
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

        $this->documentBuilder->setDataObject($object, $metadata);
        $this->assertEquals(
            [
                'data'     => [
                    'type'          => 'test_entity',
                    'id'            => '123',
                    'meta'          => [
                        'meta1' => 'Meta1',
                    ],
                    'attributes'    => [
                        'name' => 'Name',
                    ],
                    'relationships' => [
                        'category'   => [
                            'data' => [
                                'type' => 'test_category',
                                'id'   => '456'
                            ]
                        ],
                        'group'      => [
                            'data' => null
                        ],
                        'role'       => [
                            'data' => [
                                'type' => 'test_role',
                                'id'   => '789'
                            ]
                        ],
                        'categories' => [
                            'data' => [
                                [
                                    'type' => 'test_category',
                                    'id'   => '456'
                                ],
                                [
                                    'type' => 'test_category',
                                    'id'   => '457'
                                ]
                            ]
                        ],
                        'groups'     => [
                            'data' => []
                        ],
                        'products'   => [
                            'data' => []
                        ],
                        'roles'      => [
                            'data' => [
                                [
                                    'type' => 'test_role',
                                    'id'   => '789'
                                ],
                                [
                                    'type' => 'test_role',
                                    'id'   => '780'
                                ]
                            ]
                        ],
                        'otherRoles' => [
                            'data' => [
                                [
                                    'type' => 'test_role',
                                    'id'   => '789'
                                ],
                                [
                                    'type' => 'test_role',
                                    'id'   => '780'
                                ]
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type'       => 'test_role',
                        'id'         => '789',
                        'attributes' => [
                            'name' => 'Role1'
                        ]
                    ],
                    [
                        'type'       => 'test_role',
                        'id'         => '780',
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
        $metadata->addMetaProperty($this->createMetaPropertyMetadata('meta1'));
        $metadata->addAssociation($this->createAssociationMetadata('category', 'Test\Category'));
        $metadata->addAssociation($this->createAssociationMetadata('group', 'Test\Groups'));
        $metadata->addAssociation($this->createAssociationMetadata('role', 'Test\Role'));
        $metadata->addAssociation($this->createAssociationMetadata('categories', 'Test\Category', true));
        $metadata->addAssociation($this->createAssociationMetadata('groups', 'Test\Group', true));
        $metadata->addAssociation($this->createAssociationMetadata('products', 'Test\Product', true));
        $metadata->addAssociation($this->createAssociationMetadata('roles', 'Test\Role', true));
        $metadata->getAssociation('roles')->getTargetMetadata()->addField($this->createFieldMetadata('name'));

        $this->documentBuilder->setDataCollection([$object], $metadata);
        $this->assertEquals(
            [
                'data'     => [
                    [
                        'type'          => 'test_entity',
                        'id'            => '123',
                        'meta'          => [
                            'meta1' => 'Meta1',
                        ],
                        'attributes'    => [
                            'name' => 'Name',
                        ],
                        'relationships' => [
                            'category'   => [
                                'data' => [
                                    'type' => 'test_category',
                                    'id'   => '456'
                                ]
                            ],
                            'group'      => [
                                'data' => null
                            ],
                            'role'       => [
                                'data' => [
                                    'type' => 'test_role',
                                    'id'   => '789'
                                ]
                            ],
                            'categories' => [
                                'data' => [
                                    [
                                        'type' => 'test_category',
                                        'id'   => '456'
                                    ],
                                    [
                                        'type' => 'test_category',
                                        'id'   => '457'
                                    ]
                                ]
                            ],
                            'groups'     => [
                                'data' => []
                            ],
                            'products'   => [
                                'data' => []
                            ],
                            'roles'      => [
                                'data' => [
                                    [
                                        'type' => 'test_role',
                                        'id'   => '789'
                                    ],
                                    [
                                        'type' => 'test_role',
                                        'id'   => '780'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type'       => 'test_role',
                        'id'         => '789',
                        'attributes' => [
                            'name' => 'Role1'
                        ]
                    ],
                    [
                        'type'       => 'test_role',
                        'id'         => '780',
                        'attributes' => [
                            'name' => 'Role2'
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

        $this->documentBuilder->setDataObject($object, $metadata);
        $this->assertEquals(
            [
                'data'     => [
                    'type'          => 'test_entity',
                    'id'            => '123',
                    'relationships' => [
                        'categories' => [
                            'data' => [
                                [
                                    'type' => 'test_category1',
                                    'id'   => '456'
                                ],
                                [
                                    'type' => 'test_category2',
                                    'id'   => '457'
                                ]
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type'       => 'test_category1',
                        'id'         => '456',
                        'attributes' => [
                            'name' => 'Category1'
                        ]
                    ],
                    [
                        'type'       => 'test_category2',
                        'id'         => '457',
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

        $this->documentBuilder->setDataObject($object, $metadata);
        $this->assertEquals(
            [
                'data'     => [
                    'type'          => 'test_entity',
                    'id'            => '123',
                    'relationships' => [
                        'categories' => [
                            'data' => [
                                [
                                    'type' => 'test_category1',
                                    'id'   => '456'
                                ],
                                [
                                    'type' => 'test_category',
                                    'id'   => '457'
                                ]
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type'       => 'test_category1',
                        'id'         => '456',
                        'attributes' => [
                            'name' => 'Category1'
                        ]
                    ],
                    [
                        'type'       => 'test_category',
                        'id'         => '457',
                        'attributes' => [
                            'name' => 'Category2'
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
        $this->assertEquals(
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
        $this->assertEquals(
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
}
