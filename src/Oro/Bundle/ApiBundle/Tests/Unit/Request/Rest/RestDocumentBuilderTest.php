<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Request\Rest;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Request\Rest\RestDocumentBuilder;
use Oro\Bundle\ApiBundle\Tests\Unit\Request\DocumentBuilderTestCase;

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
            $this->documentBuilder->getDocument()
        );
    }

    public function testSetDataCollectionWithMetadata()
    {
        $object = [
            'id'         => 123,
            'name'       => 'Name',
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
        $metadata->addAssociation($this->createAssociationMetadata('categories', 'Test\CategoryWithoutAlias', true));
        $metadata->getAssociation('categories')->getTargetMetadata()->setInheritedType(true);
        $metadata->getAssociation('categories')->setAcceptableTargetClassNames(
            ['Test\Category1', 'Test\Category2']
        );
        $metadata->getAssociation('categories')->getTargetMetadata()->addField($this->createFieldMetadata('name'));

        $this->documentBuilder->setDataObject($object, $metadata);
        $this->assertEquals(
            [
                'id'         => 123,
                'categories' => [
                    [
                        'id'        => 456,
                        '__class__' => 'Test\Category1',
                        'name'      => 'Category1'
                    ],
                    [
                        'id'        => 457,
                        '__class__' => 'Test\Category2',
                        'name'      => 'Category2'
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
                'id'         => 123,
                'categories' => [
                    [
                        'id'        => 456,
                        '__class__' => 'Test\Category1',
                        'name'      => 'Category1'
                    ],
                    [
                        'id'        => 457,
                        '__class__' => 'Test\Category2WithoutAlias',
                        'name'      => 'Category2'
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
        $metadata = $this->getEntityMetadata('Test\Entity', ['id']);

        $this->documentBuilder->setErrorObject($error, $metadata);
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
        $metadata = $this->getEntityMetadata('Test\Entity', ['id']);

        $this->documentBuilder->setErrorCollection([$error], $metadata);
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
