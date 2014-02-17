<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\Model\Accessor;

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
        $foo = $this->createAccessor('foo');
        $bar = $this->createAccessor('bar');

        $accessor = new DelegateAccessor(array($foo, $bar));

        $this->assertAttributeEquals(array('foo' => $foo, 'bar' => $bar), 'elements', $accessor);
    }

    public function testGetName()
    {
        $this->assertEquals('delegate', $this->accessor->getName());
    }

    public function testAdd()
    {
        $this->accessor->add($foo = $this->createAccessor('foo'));
        $this->accessor->add($bar = $this->createAccessor('bar'));

        $this->assertAttributeEquals(array('foo' => $foo, 'bar' => $bar), 'elements', $this->accessor);
    }

    /**
     * @expectedException \Oro\Bundle\EntityMergeBundle\Exception\InvalidArgumentException
     * @expectedExceptionMessage Cannot add accessor to itself.
     */
    public function testAddFails()
    {
        $this->accessor->add($this->accessor);
    }

    public function testSupportsTrueLast()
    {
        $this->accessor->add($foo = $this->createAccessor('foo'));
        $this->accessor->add($bar = $this->createAccessor('bar'));
        $this->accessor->add($baz = $this->createAccessor('baz'));

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
        $this->accessor->add($foo = $this->createAccessor('foo'));
        $this->accessor->add($bar = $this->createAccessor('bar'));

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
        $this->accessor->add($foo = $this->createAccessor('foo'));
        $this->accessor->add($bar = $this->createAccessor('bar'));

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
        $this->accessor->add($foo = $this->createAccessor('foo'));

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
        $this->accessor->add($foo = $this->createAccessor('foo'));

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
        $this->accessor->add($foo = $this->createAccessor('foo'));

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

    protected function createAccessor($name)
    {
        $result = $this->getMock('Oro\Bundle\EntityMergeBundle\Model\Accessor\AccessorInterface');
        $result->expects($this->any())
            ->method('getName')
            ->will($this->returnValue($name));

        return $result;
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
