<?php

declare(strict_types=1);

namespace Oro\Bundle\SecurityBundle\Tests\Unit\EventListener;

use Nelmio\SecurityBundle\EventListener\ClickjackingListener;
use Nelmio\SecurityBundle\EventListener\ContentTypeListener;
use Nelmio\SecurityBundle\EventListener\XssProtectionListener;
use Oro\Bundle\SecurityBundle\EventListener\RedirectSecurityHeadersListenerDecorator;
use Oro\Bundle\SecurityBundle\Tests\Unit\Stub\FailingResponseListenerStub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

final class RedirectSecurityHeadersListenerDecoratorTest extends TestCase
{
    public function testGetSubscribedEvents(): void
    {
        self::assertSame(
            [KernelEvents::RESPONSE => 'onKernelResponse'],
            RedirectSecurityHeadersListenerDecorator::getSubscribedEvents()
        );
    }

    public function testRedirectResponseGetsClickjackingHeader(): void
    {
        $listener = new RedirectSecurityHeadersListenerDecorator(
            new ClickjackingListener(['^/.*' => ['header' => 'DENY']])
        );
        $response = new RedirectResponse('http://test.com/admin/user/login');
        $event = new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            Request::create('http://test.com/admin/user/'),
            HttpKernelInterface::MAIN_REQUEST,
            $response
        );

        $listener->onKernelResponse($event);

        self::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        self::assertSame('http://test.com/admin/user/login', $response->headers->get('Location'));
        self::assertSame('DENY', $response->headers->get('X-Frame-Options'));
    }

    public function testRedirectResponseGetsContentTypeHeader(): void
    {
        $listener = new RedirectSecurityHeadersListenerDecorator(new ContentTypeListener(true));
        $response = new RedirectResponse('http://test.com/admin/user/login', Response::HTTP_MOVED_PERMANENTLY);
        $event = new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            Request::create('http://test.com/admin/user/'),
            HttpKernelInterface::MAIN_REQUEST,
            $response
        );

        $listener->onKernelResponse($event);

        self::assertSame(Response::HTTP_MOVED_PERMANENTLY, $response->getStatusCode());
        self::assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
    }

    public function testRedirectResponseGetsXssProtectionHeader(): void
    {
        $listener = new RedirectSecurityHeadersListenerDecorator(new XssProtectionListener(true, true));
        $response = new RedirectResponse('http://test.com/admin/user/login');
        $event = new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            Request::create('http://test.com/admin/user/'),
            HttpKernelInterface::MAIN_REQUEST,
            $response
        );

        $listener->onKernelResponse($event);

        self::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        self::assertSame('1; mode=block', $response->headers->get('X-XSS-Protection'));
    }

    public function testNotRedirectResponse(): void
    {
        $listener = new RedirectSecurityHeadersListenerDecorator(new ContentTypeListener(true));
        $response = new Response('', Response::HTTP_NOT_FOUND);
        $event = new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            Request::create('http://test.com/admin/user/'),
            HttpKernelInterface::MAIN_REQUEST,
            $response
        );

        $listener->onKernelResponse($event);

        self::assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        self::assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
    }

    public function testStatusCodeIsRestoredWhenInnerListenerFails(): void
    {
        $innerListener = new FailingResponseListenerStub();
        $listener = new RedirectSecurityHeadersListenerDecorator($innerListener);
        $response = new RedirectResponse('http://test.com/admin/user/login');
        $event = new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            Request::create('http://test.com/admin/user/'),
            HttpKernelInterface::MAIN_REQUEST,
            $response
        );

        try {
            $listener->onKernelResponse($event);
            self::fail('The exception of the inner listener is expected.');
        } catch (\RuntimeException $e) {
            self::assertSame('Listener failed.', $e->getMessage());
        }

        self::assertSame(Response::HTTP_OK, $innerListener->statusCode);
        self::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
    }
}
