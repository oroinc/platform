<?php

declare(strict_types=1);

namespace Oro\Bundle\SecurityBundle\Tests\Unit\EventListener;

use Nelmio\SecurityBundle\ContentSecurityPolicy\DirectiveSet;
use Nelmio\SecurityBundle\ContentSecurityPolicy\NonceGenerator;
use Nelmio\SecurityBundle\ContentSecurityPolicy\PolicyManager;
use Nelmio\SecurityBundle\ContentSecurityPolicy\ShaComputer;
use Nelmio\SecurityBundle\EventListener\ContentSecurityPolicyListener;
use Oro\Bundle\SecurityBundle\EventListener\RedirectContentSecurityPolicyListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class RedirectContentSecurityPolicyListenerTest extends TestCase
{
    public function testRedirectResponseGetsContentSecurityPolicyHeader(): void
    {
        $policyManager = new PolicyManager();
        $listener = new RedirectContentSecurityPolicyListener(
            new ContentSecurityPolicyListener(
                new DirectiveSet($policyManager),
                DirectiveSet::fromConfig(
                    $policyManager,
                    ['enforce' => ['level1_fallback' => false, 'default-src' => ['self']]],
                    'enforce'
                ),
                new NonceGenerator(16),
                new ShaComputer('sha256'),
                false
            )
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
        self::assertSame("default-src 'self'", $response->headers->get('Content-Security-Policy'));
    }

    public function testNotRedirectResponseIsNotChanged(): void
    {
        $policyManager = new PolicyManager();
        $listener = new RedirectContentSecurityPolicyListener(
            new ContentSecurityPolicyListener(
                new DirectiveSet($policyManager),
                DirectiveSet::fromConfig(
                    $policyManager,
                    ['enforce' => ['level1_fallback' => false, 'default-src' => ['self']]],
                    'enforce'
                ),
                new NonceGenerator(16),
                new ShaComputer('sha256'),
                false
            )
        );
        $response = new Response('', Response::HTTP_NOT_FOUND);
        $event = new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            Request::create('http://test.com/admin/user/'),
            HttpKernelInterface::MAIN_REQUEST,
            $response
        );

        $listener->onKernelResponse($event);

        self::assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        self::assertFalse($response->headers->has('Content-Security-Policy'));
    }

    public function testNoContentSecurityPolicyListener(): void
    {
        $listener = new RedirectContentSecurityPolicyListener(null);
        $response = new RedirectResponse('http://test.com/admin/user/login');
        $headers = $response->headers->all();
        $event = new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            Request::create('http://test.com/admin/user/'),
            HttpKernelInterface::MAIN_REQUEST,
            $response
        );

        $listener->onKernelResponse($event);

        self::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        self::assertSame($headers, $response->headers->all());
    }
}
