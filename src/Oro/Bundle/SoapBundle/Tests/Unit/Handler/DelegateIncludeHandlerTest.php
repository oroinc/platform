<?php

namespace Oro\Bundle\SoapBundle\Tests\Unit\Handler;

use Oro\Bundle\SoapBundle\Handler\Context;
use Oro\Bundle\SoapBundle\Handler\DelegateIncludeHandler;
use Oro\Bundle\SoapBundle\Handler\IncludeHandlerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DelegateIncludeHandlerTest extends TestCase
{
    private Container $container;
    private DelegateIncludeHandler $handler;

    #[\Override]
    protected function setUp(): void
    {
        $this->container = new Container();

        $this->handler = new DelegateIncludeHandler($this->container);
    }

    private function createContext(): Context
    {
        return new Context(new \stdClass(), new Request(), new Response(), 'test_action', []);
    }

    public function testSupports(): void
    {
        $this->assertTrue($this->handler->supports($this->createContext()));
    }

    public function testUnknownIncludes(): void
    {
        $testUnsupported = implode(IncludeHandlerInterface::DELIMITER, ['include1', 'include2']);

        $context = $this->createContext();
        $context->getRequest()->headers->set(IncludeHandlerInterface::HEADER_INCLUDE, $testUnsupported);

        $this->handler->handle($context);

        $response = $context->getResponse();
        $this->assertTrue($response->headers->has(IncludeHandlerInterface::HEADER_UNKNOWN));
        $this->assertFalse($response->headers->has(IncludeHandlerInterface::HEADER_UNSUPPORTED));

        $this->assertSame($testUnsupported, $response->headers->get(IncludeHandlerInterface::HEADER_UNKNOWN));
    }

    public function testUnsupportedIncludes(): void
    {
        $includeName = 'lastModified';
        $context = $this->createContext();
        $context->getRequest()->headers->set(IncludeHandlerInterface::HEADER_INCLUDE, $includeName);

        $serviceId = 'acme.demo.last-modified.handler';
        $handler = $this->createMock(IncludeHandlerInterface::class);
        $this->container->set($serviceId, $handler);
        $this->handler->registerHandler($includeName, $serviceId);

        $handler->expects($this->once())
            ->method('supports')
            ->with($this->isInstanceOf(Context::class))
            ->willReturn(false);

        $this->handler->handle($context);

        $response = $context->getResponse();
        $this->assertFalse($response->headers->has(IncludeHandlerInterface::HEADER_UNKNOWN));
        $this->assertTrue($response->headers->has(IncludeHandlerInterface::HEADER_UNSUPPORTED));

        $this->assertSame($includeName, $response->headers->get(IncludeHandlerInterface::HEADER_UNSUPPORTED));
    }

    public function testSupportedIncludes(): void
    {
        $includeName = 'lastModified';
        $context = $this->createContext();
        $context->getRequest()->headers->set(IncludeHandlerInterface::HEADER_INCLUDE, $includeName);

        $serviceId = 'acme.demo.last-modified.handler';
        $handler = $this->createMock(IncludeHandlerInterface::class);
        $this->container->set($serviceId, $handler);
        $this->handler->registerHandler($includeName, $serviceId);

        $handler->expects($this->once())
            ->method('supports')
            ->with($this->isInstanceOf(Context::class))
            ->willReturn(true);
        $handler->expects($this->once())
            ->method('handle')
            ->with($this->isInstanceOf(Context::class));

        $this->handler->handle($context);

        $response = $context->getResponse();
        $this->assertFalse($response->headers->has(IncludeHandlerInterface::HEADER_UNKNOWN));
        $this->assertFalse($response->headers->has(IncludeHandlerInterface::HEADER_UNSUPPORTED));
    }
}
