<?php

namespace Oro\Bundle\PlatformBundle\Tests\Unit\ArgumentResolver;

use Oro\Bundle\PlatformBundle\ArgumentResolver\ArgumentResolver;
use Oro\Bundle\PlatformBundle\Tests\Unit\Fixtures\InvokableController;
use Oro\Bundle\PlatformBundle\Tests\Unit\Fixtures\TestController;
use Oro\Bundle\PlatformBundle\Tests\Unit\Fixtures\TestEntity;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentResolverInterface;

class ArgumentResolverTest extends \PHPUnit\Framework\TestCase
{
    private ArgumentResolverInterface|MockObject $innerResolver;
    private ArgumentResolver $argumentResolver;

    #[\Override]
    protected function setUp(): void
    {
        $this->innerResolver = $this->createMock(ArgumentResolverInterface::class);
        $this->argumentResolver = new ArgumentResolver($this->innerResolver);
    }

    public function testGetArgumentsSetsControllerArgumentsAsRequestAttributes(): void
    {
        $request = new Request();
        $controller = [new TestController(), 'action'];
        $reflector = $this->createControllerReflector($controller);
        $entity = new TestEntity();

        $arguments = [$entity];

        $this->innerResolver
            ->expects($this->once())
            ->method('getArguments')
            ->willReturn($arguments);

        $this->argumentResolver->getArguments($request, $controller, $reflector);

        $this->assertTrue($request->attributes->has('entity'));
        $this->assertSame($entity, $request->attributes->get('entity'));
    }

    public function testGetArgumentsWithInvokableController(): void
    {
        $request = new Request();
        $controller = new InvokableController();
        $reflector = $this->createControllerReflector($controller);
        $entity = new TestEntity();
        $arguments = [$entity];

        $this->innerResolver
            ->expects($this->once())
            ->method('getArguments')
            ->willReturn($arguments);

        $this->argumentResolver->getArguments($request, $controller, $reflector);

        $this->assertTrue($request->attributes->has('entity'));
        $this->assertSame($entity, $request->attributes->get('entity'));
    }

    public function testGetArgumentsWithFunctionController(): void
    {
        $request = new Request();
        $controller = function (TestEntity $entity) {
            return $entity;
        };
        $reflector = $this->createControllerReflector($controller);
        $entity = new TestEntity();
        $arguments = [$entity];

        $this->innerResolver
            ->expects($this->once())
            ->method('getArguments')
            ->willReturn($arguments);

        $this->argumentResolver->getArguments($request, $controller, $reflector);

        $this->assertTrue($request->attributes->has('entity'));
        $this->assertSame($entity, $request->attributes->get('entity'));
    }

    public function testGetArgumentsSkipsRequestParameter(): void
    {
        $request = new Request();
        $controller = [new TestController(), 'actionWithRequest'];
        $reflector = $this->createControllerReflector($controller);
        $arguments = [$request];

        $this->innerResolver
            ->expects($this->once())
            ->method('getArguments')
            ->willReturn($arguments);

        $this->argumentResolver->getArguments($request, $controller, $reflector);

        $this->assertFalse($request->attributes->has('request'));
    }

    public function testGetArgumentsDoesNotOverrideExistingAttributes(): void
    {
        $request = new Request();
        $request->attributes->set('entity', 'existing_value');

        $controller = [new TestController(), 'action'];
        $reflector = $this->createControllerReflector($controller);
        $entity = new TestEntity();
        $arguments = [$entity];

        $this->innerResolver
            ->expects($this->once())
            ->method('getArguments')
            ->willReturn($arguments);

        $this->argumentResolver->getArguments($request, $controller, $reflector);

        $this->assertSame('existing_value', $request->attributes->get('entity'));
    }

    public function testGetArgumentsWithBuiltinTypes(): void
    {
        $request = new Request();
        $controller = [new TestController(), 'actionWithBuiltinTypes'];
        $reflector = $this->createControllerReflector($controller);
        $arguments = ['string_value', 123];

        $this->innerResolver
            ->expects($this->once())
            ->method('getArguments')
            ->willReturn($arguments);

        $this->argumentResolver->getArguments($request, $controller, $reflector);

        $this->assertFalse($request->attributes->has('stringParam'));
        $this->assertFalse($request->attributes->has('intParam'));
    }

    public function testGetArgumentsWithUnionTypes(): void
    {
        $request = new Request();
        $controller = [new TestController(), 'actionWithUnionTypes'];
        $reflector = $this->createControllerReflector($controller);
        $entity = new TestEntity();
        $arguments = [$entity];

        $this->innerResolver
            ->expects($this->once())
            ->method('getArguments')
            ->willReturn($arguments);

        $this->argumentResolver->getArguments($request, $controller, $reflector);

        $this->assertTrue($request->attributes->has('entityOrString'));
        $this->assertSame($entity, $request->attributes->get('entityOrString'));
    }

    private function createControllerReflector(callable $controller): \ReflectionFunctionAbstract
    {
        if (\is_array($controller)) {
            return new \ReflectionMethod($controller[0], $controller[1]);
        }

        if (\is_object($controller) && \is_callable([$controller, '__invoke'])) {
            return new \ReflectionMethod($controller, '__invoke');
        }

        return new \ReflectionFunction($controller);
    }
}
