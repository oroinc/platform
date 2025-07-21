<?php

declare(strict_types=1);

namespace Oro\Bundle\UIBundle\Tests\Unit\Tools;

use Oro\Bundle\UIBundle\Tools\RouterRequestContextManipulator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Routing\RequestContext;

final class RouterRequestContextManipulatorTest extends TestCase
{
    private RequestContext&MockObject $context;
    private PropertyAccessorInterface&MockObject $propertyAccessor;
    private RouterRequestContextManipulator $manipulator;

    #[\Override]
    protected function setUp(): void
    {
        $this->context = $this->createMock(RequestContext::class);
        $this->propertyAccessor = $this->createMock(PropertyAccessorInterface::class);

        $this->manipulator = new RouterRequestContextManipulator($this->context, $this->propertyAccessor);
    }

    public function testSetRouterContextFromUrl(): void
    {
        $url = 'https://example.com:8443/path';

        $this->context->expects(self::once())
            ->method('setScheme')
            ->with('https');

        $this->context->expects(self::once())
            ->method('getScheme')
            ->willReturn('https');

        $this->context->expects(self::once())
            ->method('setHost')
            ->with('example.com');

        $this->context->expects(self::once())
            ->method('setHttpsPort')
            ->with(8443);

        $this->context->expects(self::once())
            ->method('setBaseUrl')
            ->with('/path');

        $this->manipulator->setRouterContextFromUrl($url);
    }

    public function testSetRouterContextFromUrlWithoutScheme(): void
    {
        $url = '//example.com:8443/path';

        $this->context->expects(self::never())
            ->method('setScheme');

        $this->context->expects(self::once())
            ->method('setHost')
            ->with('example.com');

        $this->context->expects(self::once())
            ->method('setHttpPort')
            ->with(8443);

        $this->context->expects(self::once())
            ->method('setBaseUrl')
            ->with('/path');

        $this->manipulator->setRouterContextFromUrl($url);
    }

    public function testSetRouterContextFromUrlWithoutHost(): void
    {
        $url = '/path';

        $this->context->expects(self::never())
            ->method('setScheme');

        $this->context->expects(self::never())
            ->method('setHost');

        $this->context->expects(self::never())
            ->method('setHttpPort');

        $this->context->expects(self::once())
            ->method('setBaseUrl')
            ->with('/path');

        $this->manipulator->setRouterContextFromUrl($url);
    }

    public function testSetRouterContextFromUrlWithoutPort(): void
    {
        $url = 'https://example.com/path';

        $this->context->expects(self::once())
            ->method('setScheme')
            ->with('https');

        $this->context->expects(self::once())
            ->method('setHost')
            ->with('example.com');

        $this->context->expects(self::never())
            ->method('setHttpsPort');

        $this->context->expects(self::never())
            ->method('setHttpPort');

        $this->context->expects(self::once())
            ->method('setBaseUrl')
            ->with('/path');

        $this->manipulator->setRouterContextFromUrl($url);
    }

    public function testSetRouterContextFromUrlWithoutPath(): void
    {
        $url = 'https://example.com:8443';

        $this->context->expects(self::once())
            ->method('setScheme')
            ->with('https');

        $this->context->expects(self::once())
            ->method('getScheme')
            ->willReturn('https');

        $this->context->expects(self::once())
            ->method('setHost')
            ->with('example.com');

        $this->context->expects(self::once())
            ->method('setHttpsPort')
            ->with(8443);

        $this->context->expects(self::never())
            ->method('setBaseUrl');

        $this->manipulator->setRouterContextFromUrl($url);
    }

    public function testSetRouterContextState(): void
    {
        $contextState = [
            'scheme' => 'https',
            'host' => 'example.com',
            'httpsPort' => 8443,
            'baseUrl' => '/path',
        ];

        $this->propertyAccessor->expects(self::exactly(4))
            ->method('setValue')
            ->withConsecutive(
                [$this->context, 'scheme', 'https'],
                [$this->context, 'host', 'example.com'],
                [$this->context, 'httpsPort', 8443],
                [$this->context, 'baseUrl', '/path'],
            );

        $this->manipulator->setRouterContextState($contextState);
    }

    public function testGetRouterContextStateWithHttpsScheme(): void
    {
        $this->context->expects(self::any())
            ->method('getScheme')
            ->willReturn('https');
        $this->context->expects(self::any())
            ->method('getHost')
            ->willReturn('example.com');
        $this->context->expects(self::any())
            ->method('getBaseUrl')
            ->willReturn('/path');
        $this->context->expects(self::any())
            ->method('getHttpsPort')
            ->willReturn(8443);

        $expectedState = [
            'scheme' => 'https',
            'host' => 'example.com',
            'baseUrl' => '/path',
            'httpsPort' => 8443,
        ];

        self::assertSame($expectedState, $this->manipulator->getRouterContextState());
    }

    public function testGetRouterContextStateWithHttpScheme(): void
    {
        $this->context->expects(self::any())
            ->method('getScheme')
            ->willReturn('http');
        $this->context->expects(self::any())
            ->method('getHost')
            ->willReturn('example.com');
        $this->context->expects(self::any())
            ->method('getBaseUrl')
            ->willReturn('/path');
        $this->context->expects(self::any())
            ->method('getHttpPort')
            ->willReturn(8080);

        $expectedState = [
            'scheme' => 'http',
            'host' => 'example.com',
            'baseUrl' => '/path',
            'httpPort' => 8080,
        ];

        self::assertSame($expectedState, $this->manipulator->getRouterContextState());
    }
}
