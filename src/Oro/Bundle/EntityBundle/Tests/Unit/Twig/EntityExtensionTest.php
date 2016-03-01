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

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $entityNameResolver;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $entityAliasResolver;

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
        $this->entityNameResolver  = $this->getMockBuilder('Oro\Bundle\EntityBundle\Provider\EntityNameResolver')
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityAliasResolver = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\EntityAliasResolver')
            ->disableOriginalConstructor()
            ->getMock();

        $this->twigExtension = new EntityExtension(
            $this->entityIdAccessor,
            $this->entityRoutingHelper,
            $this->entityNameResolver,
            $this->entityAliasResolver
        );
    }

    protected function tearDown()
    {
        unset($this->twigExtension);
    }

    public function testGetFunctions()
    {
        $functions = $this->twigExtension->getFunctions();
        $this->assertCount(3, $functions);

        /** @var \Twig_SimpleFunction $function */
        $function = $functions[0];
        $this->assertInstanceOf('\Twig_SimpleFunction', $function);
        $this->assertEquals('oro_class_name', $function->getName());
        $this->assertEquals([$this->twigExtension, 'getClassName'], $function->getCallable());
        $function = $functions[1];
        $this->assertInstanceOf('\Twig_SimpleFunction', $function);
        $this->assertEquals('oro_class_alias', $function->getName());
        $this->assertEquals([$this->twigExtension, 'getClassAlias'], $function->getCallable());
        $function = $functions[2];
        $this->assertInstanceOf('\Twig_SimpleFunction', $function);
        $this->assertEquals('oro_action_params', $function->getName());
        $this->assertEquals([$this->twigExtension, 'getActionParams'], $function->getCallable());
    }

    public function testGetFilters()
    {
        $filters = $this->twigExtension->getFilters();

        $this->assertCount(1, $filters);

        $this->assertInstanceOf('Twig_SimpleFilter', $filters[0]);
        $this->assertEquals('oro_format_name', $filters[0]->getName());
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
            ->method('getUrlSafeClassName');

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
            ->method('getUrlSafeClassName')
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
            ->method('getUrlSafeClassName')
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

    public function testGetEntityName()
    {
        $entity         = new \stdClass();
        $locale         = 'fr_CA';
        $expectedResult = 'John Doe';

        $this->entityNameResolver->expects($this->once())
            ->method('getName')
            ->with($this->identicalTo($entity), null, $locale)
            ->will($this->returnValue($expectedResult));

        $this->assertEquals(
            $expectedResult,
            $this->twigExtension->getEntityName($entity, $locale)
        );
    }

    public function testGetName()
    {
        $this->assertEquals('oro_entity', $this->twigExtension->getName());
    }
}
