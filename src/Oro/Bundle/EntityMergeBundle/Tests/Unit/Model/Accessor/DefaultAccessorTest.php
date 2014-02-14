<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\Model\Accessor;

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

    public function testGetName()
    {
        $this->assertEquals('default', $this->accessor->getName());
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
                'expected' => 'foo',
            ],
            'getter' => [
                'entity' => $this->createEntity('foo', $this->createEntity('bar')),
                'metadata' => $this->getFieldMetadata('id', ['getter' => 'getParentId']),
                'expected' => 'bar',
            ],
            'field' => [
                'entity' => $this->createEntity('foo'),
                'metadata' => $this->getFieldMetadata('id', ['field_name' => 'id']),
                'expected' => 'foo',
            ],
        ];
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
        return [
            'default' => [
                'entity' => $this->createEntity(),
                'metadata' => $this->getFieldMetadata('id'),
                'value' => 'foo',
                'expected' => $this->createEntity('foo'),
            ],
            'setter' => [
                'entity' => $this->createEntity('foo', $this->createEntity('bar')),
                'metadata' => $this->getFieldMetadata('id', ['setter' => 'setParentId']),
                'value' => 'baz',
                'expected' => $this->createEntity('foo', $this->createEntity('baz')),
            ],
            'field' => [
                'entity' => $this->createEntity('foo'),
                'metadata' => $this->getFieldMetadata('id', ['field_name' => 'id']),
                'value' => 'baz',
                'expected' => $this->createEntity('baz'),
            ],
        ];
    }

    protected function createEntity($id = null, $parent = null)
    {
        return new EntityStub($id, $parent);
    }

    protected function getFieldMetadata($fieldName = null, array $options = [])
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
