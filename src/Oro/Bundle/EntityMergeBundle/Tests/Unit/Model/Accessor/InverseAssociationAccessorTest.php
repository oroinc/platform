<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\Model\Accessor;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\EntityMergeBundle\Metadata\FieldMetadata;
use Oro\Bundle\EntityMergeBundle\Model\Accessor\InverseAssociationAccessor;
use Oro\Bundle\EntityMergeBundle\Tests\Unit\Stub\CollectionItemStub;
use Oro\Bundle\EntityMergeBundle\Tests\Unit\Stub\EntityStub;

class InverseAssociationAccessorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var InverseAssociationAccessor
     */
    protected $accessor;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrineHelper;

    protected function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityMergeBundle\Doctrine\DoctrineHelper')
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();

        $repository = $this
            ->getMockBuilder('Doctrine\Common\Persistence\ObjectRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $repository
            ->expects($this->any())
            ->method('findBy')
            ->will($this->returnValue([]));

        $this->doctrineHelper
            ->expects($this->any())
            ->method('getEntityRepository')
            ->will($this->returnValue($repository));

        $this->accessor = new InverseAssociationAccessor($this->doctrineHelper);
    }

    public function testGetName()
    {
        $this->assertEquals('inverse_association', $this->accessor->getName());
    }

    /**
     * @dataProvider getValueDataProvider
     */
    public function testGetValue($entity, FieldMetadata $metadata, $expectedValue)
    {
        $this->assertEquals($expectedValue, $this->accessor->getValue($entity, $metadata));
    }

    public function getValueDataProvider()
    {
        return [
            'default' => [
                'entity' => $this->createEntity('foo'),
                'metadata' => $this->getFieldMetadata('id'),
                'expected' => [],
            ]
        ];
    }

    /**
     * @dataProvider setValueDataProvider
     */
    public function testSetValue($entity, FieldMetadata $metadata, $values)
    {
        $this->accessor->setValue($entity, $metadata, $values);
        foreach ($values as $value) {
            $this->assertEquals($entity, $value->getEntityStub());
        }
    }

    public function setValueDataProvider()
    {
        return [
            'default' => [
                'entity' => $this->createEntity('foo'),
                'metadata' => $this->getFieldMetadata('entityStub'),
                'values' => new ArrayCollection([$this->createRelatedEntity('related-foo')]),
            ],
            'setter' => [
                'entity' => $this->createEntity('foo', $this->createEntity('bar')),
                'metadata' => $this->getFieldMetadata('entityStub', ['setter' => 'setEntityStub']),
                'values' => new ArrayCollection([$this->createRelatedEntity('related-foo')]),
            ],
            'reflection' => [
                'entity' => null, //@todo: approve this
                'metadata' => $this->getFieldMetadata('noGetter'),
                'values' => new ArrayCollection([$this->createRelatedEntity('related-foo')]),
            ],
        ];
    }

    protected function getFieldMetadata($fieldName = null, array $options = [])
    {
        $result = $this->getMockBuilder('Oro\Bundle\EntityMergeBundle\Metadata\FieldMetadata')
            ->disableOriginalConstructor()
            ->getMock();

        $doctrineMetadata = $this
            ->getMockBuilder('Oro\Bundle\EntityMergeBundle\Metadata\DoctrineMetadata')
            ->disableOriginalConstructor()
            ->getMock();

        $doctrineMetadata->expects($this->any())
            ->method('getFieldName')
            ->will($this->returnValue($fieldName));

        $result
            ->expects($this->any())
            ->method('getDoctrineMetadata')
            ->will($this->returnValue($doctrineMetadata));

        $result->expects($this->any())
            ->method('get')
            ->will(
                $this->returnCallback(
                    function ($code) use ($options) {
                        $this->assertArrayHasKey($code, $options);
                        return $options[$code];
                    }
                )
            );

        $result->expects($this->any())
            ->method('has')
            ->will(
                $this->returnCallback(
                    function ($code) use ($options) {
                        return isset($options[$code]);
                    }
                )
            );

        return $result;
    }

    protected function createEntity($id = null, $parent = null)
    {
        return new EntityStub($id, $parent);
    }

    protected function createRelatedEntity($id = null)
    {
        return new CollectionItemStub($id);
    }
}
