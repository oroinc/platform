<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\Model\FieldMerger;

use Oro\Bundle\EntityMergeBundle\Model\FieldMerger\CompositeFieldMerger;

class CompositeFieldMergerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CompositeFieldMerger $merger ;
     */
    protected $merger;

    protected function setUp()
    {
        $this->merger = new CompositeFieldMerger();
    }

    public function testConstructor()
    {
        $foo = $this->createFieldMerger();
        $bar = $this->createFieldMerger();

        $merger = new CompositeFieldMerger(array($foo, $bar));

        $this->assertAttributeEquals(array($foo, $bar), 'elements', $merger);
    }

    public function testAdd()
    {
        $this->merger->add($foo = $this->createFieldMerger());
        $this->merger->add($bar = $this->createFieldMerger());

        $this->assertAttributeEquals(array($foo, $bar), 'elements', $this->merger);
    }

    public function testSupportsTrueLast()
    {
        $this->merger->add($foo = $this->createFieldMerger());
        $this->merger->add($bar = $this->createFieldMerger());
        $this->merger->add($baz = $this->createFieldMerger());

        $data = $this->createFieldData();

        $foo->expects($this->once())
            ->method('supports')
            ->with($data)
            ->will($this->returnValue(false));

        $bar->expects($this->once())
            ->method('supports')
            ->with($data)
            ->will($this->returnValue(false));

        $baz->expects($this->once())
            ->method('supports')
            ->with($data)
            ->will($this->returnValue(true));

        $this->assertTrue($this->merger->supports($data));
    }

    public function testSupportsTrueFirst()
    {
        $this->merger->add($foo = $this->createFieldMerger());
        $this->merger->add($bar = $this->createFieldMerger());

        $data = $this->createFieldData();

        $foo->expects($this->once())
            ->method('supports')
            ->with($data)
            ->will($this->returnValue(true));

        $bar->expects($this->never())->method('supports');

        $this->assertTrue($this->merger->supports($data));
    }

    public function testSupportsFalse()
    {
        $this->merger->add($foo = $this->createFieldMerger());
        $this->merger->add($bar = $this->createFieldMerger());

        $data = $this->createFieldData();

        $foo->expects($this->once())
            ->method('supports')
            ->with($data)
            ->will($this->returnValue(false));

        $bar->expects($this->once())
            ->method('supports')
            ->with($data)
            ->will($this->returnValue(false));

        $this->assertFalse($this->merger->supports($data));
    }

    public function testMerge()
    {
        $this->merger->add($foo = $this->createFieldMerger());

        $data = $this->createFieldData();

        $foo->expects($this->once())
            ->method('supports')
            ->with($data)
            ->will($this->returnValue(true));

        $foo->expects($this->once())
            ->method('merge')
            ->with($data);

        $this->merger->merge($data);
    }

    /**
     * @expectedException \Oro\Bundle\EntityMergeBundle\Exception\InvalidArgumentException
     * @expectedExceptionMessage Field "test" cannot be merged.
     */
    public function testMergeFails()
    {
        $this->merger->add($foo = $this->createFieldMerger());

        $data = $this->createFieldData();

        $data->expects($this->once())
            ->method('getFieldName')
            ->will($this->returnValue('test'));

        $foo->expects($this->once())
            ->method('supports')
            ->with($data)
            ->will($this->returnValue(false));

        $this->merger->merge($data);
    }

    protected function createFieldMerger()
    {
        return $this->getMock('Oro\Bundle\EntityMergeBundle\Model\FieldMerger\FieldMergerInterface');
    }

    protected function createFieldData()
    {
        return $this->getMockBuilder('Oro\Bundle\EntityMergeBundle\Data\FieldData')
            ->disableOriginalConstructor()
            ->getMock();
    }
}
