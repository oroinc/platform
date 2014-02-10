<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\Model\Strategy;

use Oro\Bundle\EntityMergeBundle\Model\Accessor\DelegateAccessor;

class DelegateAccessorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DelegateAccessor $merger
     */
    protected $accessor;

    protected function setUp()
    {
        $this->accessor = new DelegateAccessor();
    }

    public function testConstructor()
    {
        $foo = $this->createAccessor();
        $bar = $this->createAccessor();

        $accessor = new DelegateAccessor(array($foo, $bar));

        $this->assertAttributeEquals(array($foo, $bar), 'elements', $accessor);
    }

    public function testAdd()
    {
        $this->accessor->add($foo = $this->createAccessor());
        $this->accessor->add($bar = $this->createAccessor());

        $this->assertAttributeEquals(array($foo, $bar), 'elements', $this->accessor);
    }

    public function testSupportsTrueLast()
    {
        $this->accessor->add($foo = $this->createAccessor());
        $this->accessor->add($bar = $this->createAccessor());
        $this->accessor->add($baz = $this->createAccessor());

        $entity = $this->createTestEntity(1);
        $metadata = $this->createFieldMetadata();

        $foo->expects($this->once())
            ->method('supports')
            ->with($entity, $metadata)
            ->will($this->returnValue(false));

        $bar->expects($this->once())
            ->method('supports')
            ->with($entity, $metadata)
            ->will($this->returnValue(false));

        $baz->expects($this->once())
            ->method('supports')
            ->with($entity, $metadata)
            ->will($this->returnValue(true));

        $this->assertTrue($this->accessor->supports($entity, $metadata));
    }

    public function testSupportsTrueFirst()
    {
        $this->accessor->add($foo = $this->createAccessor());
        $this->accessor->add($bar = $this->createAccessor());

        $entity = $this->createTestEntity(1);
        $metadata = $this->createFieldMetadata();

        $foo->expects($this->once())
            ->method('supports')
            ->with($entity, $metadata)
            ->will($this->returnValue(true));

        $bar->expects($this->never())->method('supports');

        $this->assertTrue($this->accessor->supports($entity, $metadata));
    }

    public function testSupportsFalse()
    {
        $this->accessor->add($foo = $this->createAccessor());
        $this->accessor->add($bar = $this->createAccessor());

        $entity = $this->createTestEntity(1);
        $metadata = $this->createFieldMetadata();

        $foo->expects($this->once())
            ->method('supports')
            ->with($entity, $metadata)
            ->will($this->returnValue(false));

        $bar->expects($this->once())
            ->method('supports')
            ->with($entity, $metadata)
            ->will($this->returnValue(false));

        $this->assertFalse($this->accessor->supports($entity, $metadata));
    }

    public function testGetValue()
    {
        $this->accessor->add($foo = $this->createAccessor());

        $entity = $this->createTestEntity(1);
        $metadata = $this->createFieldMetadata();
        $expectedResult = 'test';

        $foo->expects($this->once())
            ->method('supports')
            ->with($entity, $metadata)
            ->will($this->returnValue(true));

        $foo->expects($this->once())
            ->method('getValue')
            ->with($entity, $metadata)
            ->will($this->returnValue($expectedResult));

        $this->assertEquals($expectedResult, $this->accessor->getValue($entity, $metadata));
    }

    /**
     * @expectedException \Oro\Bundle\EntityMergeBundle\Exception\InvalidArgumentException
     * @expectedExceptionMessage Cannot find accessor for "test" field.
     */
    public function testGetValueFails()
    {
        $this->accessor->add($foo = $this->createAccessor());

        $entity = $this->createTestEntity(1);
        $metadata = $this->createFieldMetadata();

        $metadata->expects($this->once())
            ->method('getFieldName')
            ->will($this->returnValue('test'));

        $foo->expects($this->once())
            ->method('supports')
            ->with($entity, $metadata)
            ->will($this->returnValue(false));

        $this->accessor->getValue($entity, $metadata);
    }

    public function testSetValue()
    {
        $this->accessor->add($foo = $this->createAccessor());

        $entity = $this->createTestEntity(1);
        $metadata = $this->createFieldMetadata();
        $value = 'test';

        $foo->expects($this->once())
            ->method('supports')
            ->with($entity, $metadata)
            ->will($this->returnValue(true));

        $foo->expects($this->once())
            ->method('setValue')
            ->with($entity, $metadata, $value);

        $this->accessor->setValue($entity, $metadata, $value);
    }

    protected function createAccessor()
    {
        return $this->getMock('Oro\Bundle\EntityMergeBundle\Model\Accessor\AccessorInterface');
    }

    protected function createFieldMetadata()
    {
        return $this->getMockBuilder('Oro\Bundle\EntityMergeBundle\Metadata\FieldMetadata')
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function createTestEntity($id)
    {
        $result     = new \stdClass();
        $result->id = $id;
        return $result;
    }
}
