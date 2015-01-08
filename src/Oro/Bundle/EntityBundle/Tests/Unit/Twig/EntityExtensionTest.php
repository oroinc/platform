<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Twig;

use Oro\Bundle\EntityBundle\Twig\EntityExtension;
use Oro\Bundle\EntityBundle\Tests\Unit\ORM\Stub\ItemStub;
use Oro\Bundle\EntityBundle\Tests\Unit\ORM\Stub\__CG__\ItemStubProxy;

class EntityExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $entityIdAccessor;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $entityRoutingHelper;

    /** @var EntityExtension */
    protected $twigExtension;

    protected function setUp()
    {
        $this->entityIdAccessor    = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\EntityIdAccessor')
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityRoutingHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->twigExtension = new EntityExtension($this->entityIdAccessor, $this->entityRoutingHelper);
    }

    protected function tearDown()
    {
        unset($this->twigExtension);
    }

    public function testGetFunctions()
    {
        $functions = $this->twigExtension->getFunctions();
        $this->assertCount(2, $functions);

        /** @var \Twig_SimpleFunction $function */
        $function = $functions[0];
        $this->assertInstanceOf('\Twig_SimpleFunction', $function);
        $this->assertEquals('oro_class_name', $function->getName());
        $this->assertEquals([$this->twigExtension, 'getClassName'], $function->getCallable());
        $function = $functions[1];
        $this->assertInstanceOf('\Twig_SimpleFunction', $function);
        $this->assertEquals('oro_action_params', $function->getName());
        $this->assertEquals([$this->twigExtension, 'getActionParams'], $function->getCallable());
    }

    /**
     * @param string $expectedClass
     * @param mixed  $object
     *
     * @dataProvider getClassNameDataProvider
     */
    public function testGetClassName($expectedClass, $object)
    {
        $this->entityRoutingHelper->expects($this->never())
            ->method('encodeClassName');

        $this->assertEquals($expectedClass, $this->twigExtension->getClassName($object));
    }

    public function getClassNameDataProvider()
    {
        return [
            'null'          => [
                'expectedClass' => null,
                'object'        => null,
            ],
            'not an object' => [
                'expectedClass' => null,
                'object'        => 'string',
            ],
            'object'        => [
                'expectedClass' => 'Oro\Bundle\EntityBundle\Tests\Unit\ORM\Stub\ItemStub',
                'object'        => new ItemStub(),
            ],
            'proxy'         => [
                'expectedClass' => 'ItemStubProxy',
                'object'        => new ItemStubProxy(),
            ],
        ];
    }

    public function testGetClassNameEscaped()
    {
        $object        = new ItemStub();
        $class         = get_class($object);
        $expectedClass = str_replace('\\', '_', $class);

        $this->entityRoutingHelper->expects($this->once())
            ->method('encodeClassName')
            ->with($class)
            ->will($this->returnValue($expectedClass));

        $this->assertEquals(
            $expectedClass,
            $this->twigExtension->getClassName($object, true)
        );
    }

    public function testGetActionParamsNull()
    {
        $this->assertEquals(
            [],
            $this->twigExtension->getActionParams(null)
        );
    }

    public function testGetActionParamsNonObject()
    {
        $this->assertEquals(
            [],
            $this->twigExtension->getActionParams('string')
        );
    }

    public function testGetActionParams()
    {
        $object        = new ItemStub();
        $class         = get_class($object);
        $expectedClass = str_replace('\\', '_', $class);
        $objectId      = 123;
        $action        = 'test';

        $expected = ['some_val' => 'val'];

        $this->entityIdAccessor->expects($this->once())
            ->method('getIdentifier')
            ->with($this->identicalTo($object))
            ->will($this->returnValue($objectId));

        $this->entityRoutingHelper->expects($this->once())
            ->method('encodeClassName')
            ->with($class)
            ->will($this->returnValue($expectedClass));
        $this->entityRoutingHelper->expects($this->once())
            ->method('getRouteParameters')
            ->with($expectedClass, $objectId, $action)
            ->will($this->returnValue($expected));

        $this->assertEquals(
            $expected,
            $this->twigExtension->getActionParams($object, $action)
        );
    }

    public function testGetName()
    {
        $this->assertEquals('oro_entity', $this->twigExtension->getName());
    }
}
