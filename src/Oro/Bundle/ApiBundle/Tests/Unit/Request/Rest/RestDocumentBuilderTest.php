<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Request\Rest;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Request\Rest\RestDocumentBuilder;
use Oro\Bundle\ApiBundle\Tests\Unit\Request\DocumentBuilderTestCase;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

class RestDocumentBuilderTest extends DocumentBuilderTestCase
{
    /** @var RestDocumentBuilder */
    protected $documentBuilder;

    protected function setUp()
    {
        $this->documentBuilder = new RestDocumentBuilder();
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
                'id'   => 123,
                'name' => 'Name',
            ],
            $this->documentBuilder->getDocument()
        );
    }

    public function testSetDataObjectWithoutMetadataAndWithObjectType()
    {
        $object = [
            'id'        => 123,
            'name'      => 'Name',
            '__class__' => 'Test\Class'
        ];

        $this->documentBuilder->setDataObject($object);
        $this->assertEquals(
            [
                'id'     => 123,
                'name'   => 'Name',
                'entity' => 'Test\Class'
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
                [
                    'id'   => 123,
                    'name' => 'Name',
                ]
            ],
            $this->documentBuilder->getDocument()
        );
    }

    public function testSetDataCollectionWithoutMetadataAndWithObjectType()
    {
        $object = [
            'id'        => 123,
            'name'      => 'Name',
            '__class__' => 'Test\Class'
        ];

        $this->documentBuilder->setDataCollection([$object]);
        $this->assertEquals(
            [
                [
                    'id'     => 123,
                    'name'   => 'Name',
                    'entity' => 'Test\Class'
                ]
            ],
            $this->documentBuilder->getDocument()
        );
    }

    public function testSetDataCollectionOfScalarsWithoutMetadata()
    {
        $this->documentBuilder->setDataCollection(['val1', null, 'val3']);
        $this->assertEquals(
            ['val1', null, 'val3'],
            $this->documentBuilder->getDocument()
        );
    }

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

        $this->documentBuilder->setDataObject($object, $metadata);
        $this->assertEquals(
            [
                'id'         => 123,
                'name'       => 'Name',
                'meta1'      => 'Meta1',
                'category'   => 456,
                'group'      => null,
                'role'       => 789,
                'categories' => [456, 457],
                'groups'     => [],
                'products'   => [],
                'roles'      => [
                    ['id' => 789, 'name' => 'Role1'],
                    ['id' => 780, 'name' => 'Role2']
                ],
            ],
            $this->documentBuilder->getDocument()
        );
    }

    public function testSetDataCollectionWithMetadata()
    {
        $object = [
            'id'         => 123,
            'name'       => 'Name',
            'meta1'      => 'Meta1',
            'category'   => 456,
            'group'      => null,
            'role'       => 789,
            'categories' => [
                ['id' => 456],
                ['id' => 457]
            ],
            'groups'     => [],
            'products'   => [],
            'roles'      => [
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

        $this->documentBuilder->setDataCollection([$object], $metadata);
        $this->assertEquals(
            [
                [
                    'id'         => 123,
                    'name'       => 'Name',
                    'meta1'      => 'Meta1',
                    'category'   => 456,
                    'group'      => null,
                    'role'       => 789,
                    'categories' => [456, 457],
                    'groups'     => [],
                    'products'   => [],
                    'roles'      => [
                        ['id' => 789, 'name' => 'Role1'],
                        ['id' => 780, 'name' => 'Role2']
                    ]
                ]
            ],
            $this->documentBuilder->getDocument()
        );
    }

    public function testSetDataCollectionOfScalarsWithMetadata()
    {
        $metadata = $this->getEntityMetadata('Test\Entity', ['id']);
        $metadata->addField($this->createFieldMetadata('id'));
        $metadata->addField($this->createFieldMetadata('name'));

        $this->documentBuilder->setDataCollection(['val1', null, 'val3'], $metadata);
        $this->assertEquals(
            ['val1', null, 'val3'],
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
        $categoriesMetadata = $metadata->addAssociation(
            $this->createAssociationMetadata('categories', 'Test\CategoryWithoutAlias', true)
        );
        $categoriesMetadata->getTargetMetadata()->setInheritedType(true);
        $categoriesMetadata->setAcceptableTargetClassNames(['Test\Category1', 'Test\Category2']);
        $categoriesMetadata->getTargetMetadata()->addField($this->createFieldMetadata('name'));
        $categoriesMetadata->getTargetMetadata()->addMetaProperty(
            $this->createMetaPropertyMetadata(ConfigUtil::CLASS_NAME)
        );

        $this->documentBuilder->setDataObject($object, $metadata);
        $this->assertEquals(
            [
                'id'         => 123,
                'categories' => [
                    [
                        'entity' => 'Test\Category1',
                        'id'     => 456,
                        'name'   => 'Category1'
                    ],
                    [
                        'entity' => 'Test\Category2',
                        'id'     => 457,
                        'name'   => 'Category2'
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
        $categoriesMetadata = $metadata->addAssociation(
            $this->createAssociationMetadata('categories', 'Test\Category', true)
        );
        $categoriesMetadata->getTargetMetadata()->setInheritedType(true);
        $categoriesMetadata->setAcceptableTargetClassNames(['Test\Category1', 'Test\Category2WithoutAlias']);
        $categoriesMetadata->getTargetMetadata()->addField($this->createFieldMetadata('name'));
        $categoriesMetadata->getTargetMetadata()->addMetaProperty(
            $this->createMetaPropertyMetadata(ConfigUtil::CLASS_NAME)
        );

        $this->documentBuilder->setDataObject($object, $metadata);
        $this->assertEquals(
            [
                'id'         => 123,
                'categories' => [
                    [
                        'entity' => 'Test\Category1',
                        'id'     => 456,
                        'name'   => 'Category1'
                    ],
                    [
                        'entity' => 'Test\Category2WithoutAlias',
                        'id'     => 457,
                        'name'   => 'Category2'
                    ]
                ]
            ],
            $this->documentBuilder->getDocument()
        );
    }

    public function testMissingAssociationsAsFields()
    {
        $object = [
            'id' => 123,
        ];

        $metadata = $this->getEntityMetadata('Test\Entity', ['id']);
        $metadata->addField($this->createFieldMetadata('id'));
        $metadata->addAssociation($this->createAssociationMetadata('missingToOne', 'Test\Class'));
        $metadata->addAssociation($this->createAssociationMetadata('missingToMany', 'Test\Class', true));
        foreach ($metadata->getAssociations() as $association) {
            $association->setDataType('array');
        }

        $this->documentBuilder->setDataObject($object, $metadata);
        $this->assertEquals(
            [
                'id'            => 123,
                'missingToOne'  => null,
                'missingToMany' => []
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
            'category' => $value,
        ];

        $metadata = $this->getEntityMetadata('Test\Entity', ['id']);
        $metadata->addField($this->createFieldMetadata('id'));
        $association = $metadata->addAssociation(
            $this->createAssociationMetadata('category', 'Test\Category')
        );
        $association->setDataType('scalar');
        $association->getTargetMetadata()->addField($this->createFieldMetadata('name'));

        $this->documentBuilder->setDataObject($object, $metadata);
        $this->assertEquals(
            [
                'id'       => 123,
                'category' => $expected
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
                ['id' => 123, 'name' => null],
            ],
            [
                ['id' => 123, 'name' => 'name1'],
                ['id' => 123, 'name' => 'name1'],
            ],
            [
                ['id' => 123, 'name' => 'name1', 'other' => 'val1'],
                ['id' => 123, 'name' => 'name1'],
            ],
        ];
    }

    /**
     * @dataProvider toManyAssociationAsFieldProvider
     */
    public function testToManyAssociationAsField($value, $expected)
    {
        $object = [
            'id'         => 123,
            'categories' => $value,
        ];

        $metadata = $this->getEntityMetadata('Test\Entity', ['id']);
        $metadata->addField($this->createFieldMetadata('id'));
        $association = $metadata->addAssociation(
            $this->createAssociationMetadata('categories', 'Test\Category', true)
        );
        $association->setDataType('array');
        $association->getTargetMetadata()->addField($this->createFieldMetadata('name'));

        $this->documentBuilder->setDataObject($object, $metadata);
        $this->assertEquals(
            [
                'id'         => 123,
                'categories' => $expected
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
                [['id' => 123, 'name' => null], ['id' => 124, 'name' => null]],
            ],
            [
                [['id' => 123, 'name' => 'name1'], ['id' => 124, 'name' => 'name2']],
                [['id' => 123, 'name' => 'name1'], ['id' => 124, 'name' => 'name2']],
            ],
            [
                [
                    ['id' => 123, 'name' => 'name1', 'other' => 'val1'],
                    ['id' => 124, 'name' => 'name2', 'other' => 'val1']
                ],
                [['id' => 123, 'name' => 'name1'], ['id' => 124, 'name' => 'name2']],
            ],
        ];
    }

    /**
     * @dataProvider toOneAssociationAsFieldForIdFieldsOnlyProvider
     */
    public function testToOneAssociationAsFieldForIdFieldsOnly($value, $expected)
    {
        $object = [
            'id'       => 123,
            'category' => $value,
        ];

        $metadata = $this->getEntityMetadata('Test\Entity', ['id']);
        $metadata->addField($this->createFieldMetadata('id'));
        $association = $metadata->addAssociation(
            $this->createAssociationMetadata('category', 'Test\Category')
        );
        $association->setDataType('scalar');

        $this->documentBuilder->setDataObject($object, $metadata);
        $this->assertEquals(
            [
                    'id'       => 123,
                    'category' => $expected
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
            [['id' => 123, 'name' => 'name1'], 123],
        ];
    }

    /**
     * @dataProvider toManyAssociationAsFieldForIdFieldsOnlyProvider
     */
    public function testToManyAssociationAsFieldForIdFieldsOnly($value, $expected)
    {
        $object = [
            'id'         => 123,
            'categories' => $value,
        ];

        $metadata = $this->getEntityMetadata('Test\Entity', ['id']);
        $metadata->addField($this->createFieldMetadata('id'));
        $association = $metadata->addAssociation(
            $this->createAssociationMetadata('categories', 'Test\Category', true)
        );
        $association->setDataType('array');

        $this->documentBuilder->setDataObject($object, $metadata);
        $this->assertEquals(
            [
                'id'         => 123,
                'categories' => $expected
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
            ],
        ];
    }

    /**
     * @dataProvider toOneCollapsedAssociationAsFieldProvider
     */
    public function testToOneCollapsedAssociationAsField($value, $expected)
    {
        $object = [
            'id'       => 123,
            'category' => $value,
        ];

        $metadata = $this->getEntityMetadata('Test\Entity', ['id']);
        $metadata->addField($this->createFieldMetadata('id'));
        $association = $metadata->addAssociation(
            $this->createAssociationMetadata('category', 'Test\Category')
        );
        $association->setDataType('scalar');
        $association->setCollapsed('scalar');
        $association->getTargetMetadata()->removeField('id');
        $association->getTargetMetadata()->addField($this->createFieldMetadata('name'));

        $this->documentBuilder->setDataObject($object, $metadata);
        $this->assertEquals(
            [
                'id'       => 123,
                'category' => $expected
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
                'name1',
            ],
            [
                ['name' => 'name1', 'other' => 'val1'],
                'name1',
            ],
        ];
    }

    /**
     * @dataProvider toManyCollapsedAssociationAsFieldProvider
     */
    public function testToManyCollapsedAssociationAsField($value, $expected)
    {
        $object = [
            'id'         => 123,
            'categories' => $value,
        ];

        $metadata = $this->getEntityMetadata('Test\Entity', ['id']);
        $metadata->addField($this->createFieldMetadata('id'));
        $association = $metadata->addAssociation(
            $this->createAssociationMetadata('categories', 'Test\Category', true)
        );
        $association->setDataType('array');
        $association->setCollapsed('scalar');
        $association->getTargetMetadata()->removeField('id');
        $association->getTargetMetadata()->addField($this->createFieldMetadata('name'));

        $this->documentBuilder->setDataObject($object, $metadata);
        $this->assertEquals(
            [
                'id'         => 123,
                'categories' => $expected
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
                ['name1', 'name2'],
            ],
            [
                [
                    ['name' => 'name1', 'other' => 'val1'],
                    ['name' => 'name2', 'other' => 'val1']
                ],
                ['name1', 'name2'],
            ],
        ];
    }

    public function testNestedAssociationAsArrayAttribute()
    {
        $object = [
            'association' => [
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
            ],
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

        $this->documentBuilder->setDataObject($object, $metadata);
        $this->assertEquals(
            [
                'association' => [
                    'id'            => 123,
                    'name'          => 'Name',
                    'meta1'         => 'Meta1',
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
                ],
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
                [
                    'code'   => 'errCode',
                    'title'  => 'some error',
                    'detail' => 'some error details'
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
                [
                    'code'   => 'errCode',
                    'title'  => 'some error',
                    'detail' => 'some error details'
                ]
            ],
            $this->documentBuilder->getDocument()
        );
    }
}
