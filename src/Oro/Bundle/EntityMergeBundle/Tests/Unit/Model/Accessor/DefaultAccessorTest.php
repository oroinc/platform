<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\Model;

use Oro\Bundle\EntityMergeBundle\Metadata\FieldMetadata;
use Oro\Bundle\EntityMergeBundle\Model\Accessor\DefaultAccessor;

use Oro\Bundle\EntityMergeBundle\Tests\Unit\Stub\EntityStub;

class DefaultAccessorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DefaultAccessor $fieldAccessor;
     */
    protected $accessor;

    protected function setUp()
    {
        $this->accessor = new DefaultAccessor();
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
        return array(
            'default' => array(
                'entity' => $this->createEntity('foo'),
                'metadata' => $this->getFieldMetadata('id'),
                'expected' => 'foo',
            ),
            'getter' => array(
                'entity' => $this->createEntity('foo', $this->createEntity('bar')),
                'metadata' => $this->getFieldMetadata('id', array('getter' => 'getParentId')),
                'expected' => 'bar',
            ),
            'property_path' => array(
                'entity' => $this->createEntity('foo', $this->createEntity('bar')),
                'metadata' => $this->getFieldMetadata('id', array('property_path' => 'parent.id')),
                'expected' => 'bar',
            ),
        );
    }

    /**
     * @dataProvider setValueDataProvider
     */
    public function testSetValue($entity, FieldMetadata $metadata, $value, $expectedEntity)
    {
        $this->accessor->setValue($entity, $metadata, $value);
        $this->assertEquals($expectedEntity, $entity);
    }

    public function setValueDataProvider()
    {
        return array(
            'default' => array(
                'entity' => $this->createEntity(),
                'metadata' => $this->getFieldMetadata('id'),
                'value' => 'foo',
                'expected' => $this->createEntity('foo'),
            ),
            'setter' => array(
                'entity' => $this->createEntity('foo', $this->createEntity('bar')),
                'metadata' => $this->getFieldMetadata('id', array('setter' => 'setParentId')),
                'value' => 'baz',
                'expected' => $this->createEntity('foo', $this->createEntity('baz')),
            ),
            'property_path' => array(
                'entity' => $this->createEntity('foo', $this->createEntity('bar')),
                'metadata' => $this->getFieldMetadata('id', array('property_path' => 'parent.id')),
                'value' => 'baz',
                'expected' => $this->createEntity('foo', $this->createEntity('baz')),
            ),
        );
    }

    public function testGetName()
    {
        $this->assertEquals('default', $this->accessor->getName());
    }

    protected function createEntity($id = null, $parent = null)
    {
        return new EntityStub($id, $parent);
    }

    protected function getFieldMetadata($fieldName = null, array $options = array())
    {
        $result = $this->getMockBuilder('Oro\Bundle\EntityMergeBundle\Metadata\FieldMetadata')
            ->disableOriginalConstructor()
            ->getMock();

        $result->expects($this->any())
            ->method('getFieldName')
            ->will($this->returnValue($fieldName));

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
}
