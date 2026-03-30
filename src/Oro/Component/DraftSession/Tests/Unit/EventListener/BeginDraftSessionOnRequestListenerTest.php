<?php

declare(strict_types=1);

namespace Oro\Component\DraftSession\Tests\Unit\EventListener;

use Oro\Component\DraftSession\EventListener\BeginDraftSessionOnRequestListener;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class BeginDraftSessionOnRequestListenerTest extends TestCase
{
    private UrlGeneratorInterface&MockObject $urlGenerator;
    private BeginDraftSessionOnRequestListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $this->listener = new BeginDraftSessionOnRequestListener(
            $this->urlGenerator,
            'draftSessionUuid',
            ['test_route', 'custom_route']
        );
    }

    public function testOnKernelRequestDoesNothingForSubRequest(): void
    {
        $request = new Request();
        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::SUB_REQUEST
        );

        $this->urlGenerator->expects(self::never())
            ->method('generate');

        $this->listener->onKernelRequest($event);

        self::assertNull($event->getResponse());
    }

    public function testOnKernelRequestDoesNothingForUnsafeMethod(): void
    {
        $request = Request::create('/', 'POST');
        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $this->urlGenerator->expects(self::never())
            ->method('generate');

        $this->listener->onKernelRequest($event);

        self::assertNull($event->getResponse());
    }

    public function testOnKernelRequestDoesNothingWhenUuidAlreadyPresent(): void
    {
        $request = new Request();
        $request->attributes->set('draftSessionUuid', 'existing-uuid-123');
        $request->attributes->set('_route_params', ['draftSessionUuid' => 'existing-uuid-123']);

        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $this->urlGenerator->expects(self::never())
            ->method('generate');

        $this->listener->onKernelRequest($event);

        self::assertNull($event->getResponse());
    }

    public function testOnKernelRequestDoesNothingWhenParameterNotInRouteParams(): void
    {
        $request = new Request();
        $request->attributes->set('_route', 'non_applicable_route');
        $request->attributes->set('_route_params', ['draftSessionUuid' => '']);
        $request->attributes->set('draftSessionUuid', '');

        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $this->urlGenerator->expects(self::never())
            ->method('generate');

        $this->listener->onKernelRequest($event);

        self::assertNull($event->getResponse());
    }

    public function testOnKernelRequestDoesNothingWhenRouteIsNull(): void
    {
        $request = new Request();
        $request->attributes->set('_route', null);
        $request->attributes->set('_route_params', ['draftSessionUuid' => '']);
        $request->attributes->set('draftSessionUuid', '');

        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $this->urlGenerator->expects(self::never())
            ->method('generate');

        $this->listener->onKernelRequest($event);

        self::assertNull($event->getResponse());
    }

    public function testOnKernelRequestDoesNothingWhenRouteNotInApplicableRoutes(): void
    {
        $request = new Request();
        $request->attributes->set('_route', 'not_applicable_route');
        $request->attributes->set('_route_params', ['draftSessionUuid' => '']);
        $request->attributes->set('draftSessionUuid', '');

        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $this->urlGenerator->expects(self::never())
            ->method('generate');

        $this->listener->onKernelRequest($event);

        self::assertNull($event->getResponse());
    }

    public function testOnKernelRequestGeneratesUuidAndRedirects(): void
    {
        $request = new Request();
        $request->attributes->set('_route', 'test_route');
        $request->attributes->set('_route_params', ['draftSessionUuid' => '', 'id' => 123]);
        $request->attributes->set('draftSessionUuid', '');

        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $capturedParams = null;
        $this->urlGenerator
            ->expects(self::once())
            ->method('generate')
            ->willReturnCallback(function ($route, $params) use (&$capturedParams) {
                $capturedParams = $params;

                return '/test-route/123?draftSessionUuid=generated-uuid';
            });

        $this->listener->onKernelRequest($event);

        self::assertEquals('test_route', $request->attributes->get('_route'));
        self::assertArrayHasKey('draftSessionUuid', $capturedParams);
        self::assertArrayHasKey('id', $capturedParams);
        self::assertEquals(123, $capturedParams['id']);
        self::assertNotEmpty($capturedParams['draftSessionUuid']);
        self::assertIsString($capturedParams['draftSessionUuid']);
        self::assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $capturedParams['draftSessionUuid']
        );
        self::assertNotNull($event->getResponse());
        self::assertInstanceOf(RedirectResponse::class, $event->getResponse());
        self::assertEquals('/test-route/123?draftSessionUuid=generated-uuid', $event->getResponse()->getTargetUrl());
    }

    public function testOnKernelRequestWithCustomParameterName(): void
    {
        $listener = new BeginDraftSessionOnRequestListener(
            $this->urlGenerator,
            'customUuid',
            ['custom_route']
        );

        $request = new Request();
        $request->attributes->set('_route', 'custom_route');
        $request->attributes->set('_route_params', ['customUuid' => null]);
        $request->attributes->set('customUuid', null);

        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $capturedParams = null;
        $this->urlGenerator
            ->expects(self::once())
            ->method('generate')
            ->willReturnCallback(function ($route, $params) use (&$capturedParams) {
                $capturedParams = $params;

                return '/custom-route?customUuid=generated-uuid';
            });

        $listener->onKernelRequest($event);

        self::assertArrayHasKey('customUuid', $capturedParams);
        self::assertNotEmpty($capturedParams['customUuid']);
        self::assertInstanceOf(RedirectResponse::class, $event->getResponse());
    }

    public function testOnKernelRequestWithEmptyStringUuid(): void
    {
        $request = new Request();
        $request->attributes->set('_route', 'test_route');
        $request->attributes->set('_route_params', ['draftSessionUuid' => '']);
        $request->attributes->set('draftSessionUuid', '');

        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $this->urlGenerator->expects(self::once())
            ->method('generate')
            ->willReturn('/test-route?draftSessionUuid=generated-uuid');

        $this->listener->onKernelRequest($event);

        self::assertInstanceOf(RedirectResponse::class, $event->getResponse());
    }

    public function testOnKernelRequestWithNullUuid(): void
    {
        $request = new Request();
        $request->attributes->set('_route', 'test_route');
        $request->attributes->set('_route_params', ['draftSessionUuid' => null]);
        $request->attributes->set('draftSessionUuid', null);

        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $this->urlGenerator->expects(self::once())
            ->method('generate')
            ->willReturn('/test-route?draftSessionUuid=generated-uuid');

        $this->listener->onKernelRequest($event);

        self::assertInstanceOf(RedirectResponse::class, $event->getResponse());
    }

    public function testOnKernelRequestRedirectsWhenUuidIsNonString(): void
    {
        $request = new Request();
        $request->attributes->set('_route', 'test_route');
        $request->attributes->set('_route_params', ['draftSessionUuid' => 123]);
        $request->attributes->set('draftSessionUuid', 123);

        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $this->urlGenerator->expects(self::once())
            ->method('generate')
            ->willReturn('/test-route?draftSessionUuid=generated-uuid');

        $this->listener->onKernelRequest($event);

        self::assertInstanceOf(RedirectResponse::class, $event->getResponse());
    }

    public function testOnKernelRequestPreservesQueryParameters(): void
    {
        $request = new Request(['foo' => 'bar', 'baz' => 'qux']);
        $request->attributes->set('_route', 'test_route');
        $request->attributes->set('_route_params', ['draftSessionUuid' => '', 'id' => 456]);
        $request->attributes->set('draftSessionUuid', '');

        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $capturedParams = null;
        $this->urlGenerator
            ->expects(self::once())
            ->method('generate')
            ->willReturnCallback(function ($route, $params) use (&$capturedParams) {
                $capturedParams = $params;

                return '/test-route/456?draftSessionUuid=generated-uuid&foo=bar&baz=qux';
            });

        $this->listener->onKernelRequest($event);

        self::assertArrayHasKey('draftSessionUuid', $capturedParams);
        self::assertArrayHasKey('id', $capturedParams);
        self::assertArrayHasKey('foo', $capturedParams);
        self::assertArrayHasKey('baz', $capturedParams);
        self::assertEquals(456, $capturedParams['id']);
        self::assertEquals('bar', $capturedParams['foo']);
        self::assertEquals('qux', $capturedParams['baz']);
        self::assertNotEmpty($capturedParams['draftSessionUuid']);
        self::assertNotNull($event->getResponse());
        self::assertInstanceOf(RedirectResponse::class, $event->getResponse());
        self::assertEquals(
            '/test-route/456?draftSessionUuid=generated-uuid&foo=bar&baz=qux',
            $event->getResponse()->getTargetUrl()
        );
    }
}
