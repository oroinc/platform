<?php

namespace Oro\Bundle\PlatformBundle\Tests\Unit\EventListener\Controller;

use Oro\Bundle\PlatformBundle\EventListener\Controller\ControllerListener;
use Oro\Bundle\PlatformBundle\Tests\Unit\Fixtures\AttributedTestController;
use Oro\Bundle\PlatformBundle\Tests\Unit\Fixtures\InvokableController;
use Oro\Bundle\PlatformBundle\Tests\Unit\Fixtures\SharedAliasTestAttribute;
use Oro\Bundle\PlatformBundle\Tests\Unit\Fixtures\TestArrayAttribute;
use Oro\Bundle\PlatformBundle\Tests\Unit\Fixtures\TestAttribute;
use Oro\Bundle\PlatformBundle\Tests\Unit\Fixtures\TestController;
use Oro\Component\PhpUtils\Attribute\Reader\AttributeReader;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class ControllerListenerTest extends \PHPUnit\Framework\TestCase
{
    private ControllerListener $listener;
    private AttributeReader $attributeReader;

    protected function setUp(): void
    {
        $this->attributeReader = $this->createMock(AttributeReader::class);
        $this->listener = new ControllerListener($this->attributeReader);
    }

    public function testOnKernelControllerWithNonArrayController(): void
    {
        $request = new Request();
        $kernel = $this->createMock(HttpKernelInterface::class);

        $stringController = 'strlen';
        $event = new ControllerEvent($kernel, $stringController, $request, HttpKernelInterface::MAIN_REQUEST);

        $this->attributeReader->expects($this->never())->method('getClassAttributes');
        $this->attributeReader->expects($this->never())->method('getMethodAttributes');

        $this->listener->onKernelController($event);

        $this->assertEmpty($request->attributes->all());
    }

    public function testOnKernelControllerWithInvokableController(): void
    {
        $request = new Request();
        $kernel = $this->createMock(HttpKernelInterface::class);
        $controller = new InvokableController();

        $event = new ControllerEvent($kernel, $controller, $request, HttpKernelInterface::MAIN_REQUEST);

        $this->attributeReader->expects($this->once())
            ->method('getClassAttributes')
            ->willReturn([]);

        $this->attributeReader->expects($this->once())
            ->method('getMethodAttributes')
            ->willReturn([]);

        $this->listener->onKernelController($event);

        $this->assertEmpty($request->attributes->all());
    }

    public function testOnKernelControllerWithoutAttributes(): void
    {
        $request = new Request();
        $kernel = $this->createMock(HttpKernelInterface::class);
        $controller = [new TestController(), 'actionWithRequest'];

        $event = new ControllerEvent($kernel, $controller, $request, HttpKernelInterface::MAIN_REQUEST);

        $this->attributeReader->expects($this->once())
            ->method('getClassAttributes')
            ->willReturn([]);

        $this->attributeReader->expects($this->once())
            ->method('getMethodAttributes')
            ->willReturn([]);

        $this->listener->onKernelController($event);

        $this->assertEmpty($request->attributes->all());
    }

    public function testOnKernelControllerWithRealAttributes(): void
    {
        $request = new Request();
        $kernel = $this->createMock(HttpKernelInterface::class);

        $controller = [new AttributedTestController(), 'methodAttributeAction'];

        $event = new ControllerEvent($kernel, $controller, $request, HttpKernelInterface::MAIN_REQUEST);

        $this->attributeReader->expects($this->once())
            ->method('getClassAttributes')
            ->willReturn([]);

        $this->attributeReader->expects($this->once())
            ->method('getMethodAttributes')
            ->willReturn([]);

        $this->listener->onKernelController($event);

        $this->assertTrue($request->attributes->has('_test_attribute'));
        $this->assertTrue($request->attributes->has('_test_array_attribute'));
    }

    public function testOnKernelControllerWithAttributeReaderConfigurations(): void
    {
        $request = new Request();
        $kernel = $this->createMock(HttpKernelInterface::class);
        $controller = [new TestController(), 'actionWithRequest'];

        $event = new ControllerEvent($kernel, $controller, $request, HttpKernelInterface::MAIN_REQUEST);

        $classTestAttribute = new TestAttribute('class_value', true);
        $methodTestAttribute = new TestAttribute('method_value', false);

        $this->attributeReader->expects($this->once())
            ->method('getClassAttributes')
            ->willReturn([$classTestAttribute]);

        $this->attributeReader->expects($this->once())
            ->method('getMethodAttributes')
            ->willReturn([$methodTestAttribute]);

        $this->listener->onKernelController($event);

        $this->assertTrue($request->attributes->has('_test_attribute'));
        $testAttribute = $request->attributes->get('_test_attribute');
        $this->assertInstanceOf(TestAttribute::class, $testAttribute);
        $this->assertEquals('method_value', $testAttribute->getValue());
        $this->assertFalse($testAttribute->isEnabled());
    }

    public function testOnKernelControllerWithArrayAttributesMerging(): void
    {
        $request = new Request();
        $kernel = $this->createMock(HttpKernelInterface::class);
        $controller = [new TestController(), 'actionWithRequest'];

        $event = new ControllerEvent($kernel, $controller, $request, HttpKernelInterface::MAIN_REQUEST);

        $classArrayAttribute1 = new TestArrayAttribute('first', 'data1');
        $classArrayAttribute2 = new TestArrayAttribute('second', 'data2');
        $methodArrayAttribute = new TestArrayAttribute('method_array', 'method_array_data');

        $this->attributeReader->expects($this->once())
            ->method('getClassAttributes')
            ->willReturn([$classArrayAttribute1, $classArrayAttribute2]);

        $this->attributeReader->expects($this->once())
            ->method('getMethodAttributes')
            ->willReturn([$methodArrayAttribute]);

        $this->listener->onKernelController($event);

        $this->assertTrue($request->attributes->has('_test_array_attribute'));
        $arrayAttributes = $request->attributes->get('_test_array_attribute');
        $this->assertIsArray($arrayAttributes);
        $this->assertCount(3, $arrayAttributes);
    }

    public function testOnKernelControllerWithMismatchedArrayConfiguration(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Configurations should both be an array or both not be an array.');

        $request = new Request();
        $kernel = $this->createMock(HttpKernelInterface::class);
        $controller = [new TestController(), 'actionWithRequest'];

        $event = new ControllerEvent($kernel, $controller, $request, HttpKernelInterface::MAIN_REQUEST);

        $classArrayAttribute1 = new SharedAliasTestAttribute('class1', true);
        $classArrayAttribute2 = new SharedAliasTestAttribute('class2', true);

        $methodSingleAttribute = new SharedAliasTestAttribute('method', false);

        $this->attributeReader->expects($this->once())
            ->method('getClassAttributes')
            ->willReturn([$classArrayAttribute1, $classArrayAttribute2]);

        $this->attributeReader->expects($this->once())
            ->method('getMethodAttributes')
            ->willReturn([$methodSingleAttribute]);

        $this->listener->onKernelController($event);
    }

    public function testOnKernelControllerWithClosureController(): void
    {
        $request = new Request();
        $kernel = $this->createMock(HttpKernelInterface::class);

        $closure = function () {
            return 'test';
        };
        $event = new ControllerEvent($kernel, $closure, $request, HttpKernelInterface::MAIN_REQUEST);

        $this->attributeReader->expects($this->once())
            ->method('getClassAttributes')
            ->willReturn([]);

        $this->attributeReader->expects($this->once())
            ->method('getMethodAttributes')
            ->willReturn([]);

        $this->listener->onKernelController($event);

        $this->assertEmpty($request->attributes->all());
    }
}
