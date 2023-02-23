<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Event;

use Oro\Bundle\NavigationBundle\Event\ResponseHashnavListener;
use Oro\Component\Testing\Unit\TestContainerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Twig\Environment;

class ResponseHashnavListenerTest extends \PHPUnit\Framework\TestCase
{
    private const TEST_URL = 'http://test_url/';
    private const TEMPLATE = '@OroNavigation/HashNav/redirect.html.twig';

    /** @var Request */
    private $request;

    /** @var Response */
    private $response;

    /** @var Environment|\PHPUnit\Framework\MockObject\MockObject */
    private $twig;

    /** @var ResponseEvent|\PHPUnit\Framework\MockObject\MockObject */
    private $event;

    /** @var TokenStorageInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenStorage;

    /** @var ResponseHashnavListener */
    private $listener;

    protected function setUp(): void
    {
        $this->response = new Response();
        $this->request = Request::create(self::TEST_URL);
        $this->request->headers->add([ResponseHashnavListener::HASH_NAVIGATION_HEADER => true]);
        $this->event = new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            $this->request,
            HttpKernelInterface::MAIN_REQUEST,
            $this->response
        );

        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->twig = $this->createMock(Environment::class);
        $this->listener = $this->getListener(false);
    }

    public function testPlainRequest(): void
    {
        $testBody = 'test';
        $this->response->setContent($testBody);

        $this->listener->onResponse($this->event);

        self::assertEquals($testBody, $this->response->getContent());
    }

    public function testHashRequestWOUser(): void
    {
        $this->response->setStatusCode(302);
        $this->response->headers->add(['location' => self::TEST_URL]);

        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn(false);

        $template = 'rendered_template_content';
        $this->twig->expects(self::once())
            ->method('render')
            ->with(
                self::TEMPLATE,
                [
                    'full_redirect' => true,
                    'location'      => self::TEST_URL
                ]
            )
            ->willReturn($template);

        $this->listener->onResponse($this->event);

        self::assertEquals(200, $this->response->getStatusCode());
        self::assertEquals($template, $this->response->getContent());
    }

    public function testHashRequestWithFullRedirectAttribute(): void
    {
        $this->response->setStatusCode(302);
        $this->response->headers->add(['location' => self::TEST_URL]);

        $this->request->attributes->set('_fullRedirect', true);

        $this->tokenStorage->expects(self::never())
            ->method('getToken');

        $template = 'rendered_template_content';
        $this->twig->expects(self::once())
            ->method('render')
            ->with(
                self::TEMPLATE,
                [
                    'full_redirect' => true,
                    'location'      => self::TEST_URL
                ]
            )
            ->willReturn($template);

        $this->listener->onResponse($this->event);

        self::assertEquals(200, $this->response->getStatusCode());
        self::assertEquals($template, $this->response->getContent());
    }

    public function testHashRequestNotFound(): void
    {
        $this->response->setStatusCode(404);
        $this->serverErrorHandle();
    }

    public function testFullRedirectProducedInProdEnv(): void
    {
        $expected = ['full_redirect' => 1, 'location' => self::TEST_URL];
        $this->response->headers->add(['location' => self::TEST_URL]);
        $this->response->setStatusCode(503);

        $template = 'rendered_template_content';
        $this->twig->expects(self::once())
            ->method('render')
            ->with('@OroNavigation/HashNav/redirect.html.twig', $expected)
            ->willReturn($template);

        $this->listener->onResponse($this->event);

        self::assertEquals(200, $this->response->getStatusCode());
        self::assertEquals($template, $this->response->getContent());
    }

    public function testFullRedirectNotProducedInDevEnv(): void
    {
        $listener = $this->getListener(true);
        $this->response->headers->add(['location' => self::TEST_URL]);
        $this->response->setStatusCode(503);
        $this->twig->expects(self::never())
            ->method('render');

        $listener->onResponse($this->event);

        self::assertEquals(503, $this->response->getStatusCode());
        self::assertEmpty($this->response->getContent());
    }

    private function getListener(bool $debug): ResponseHashnavListener
    {
        $container = TestContainerBuilder::create()
            ->add(Environment::class, $this->twig)
            ->getContainer($this);

        return new ResponseHashnavListener($this->tokenStorage, $debug, $container);
    }

    private function serverErrorHandle(): void
    {
        $template = 'rendered_template_content';
        $this->twig->expects(self::once())
            ->method('render')
            ->with(
                self::TEMPLATE,
                [
                    'full_redirect' => true,
                    'location'      => self::TEST_URL
                ]
            )
            ->willReturn($template);

        $this->listener->onResponse($this->event);

        self::assertEquals(200, $this->response->getStatusCode());
        self::assertEquals($template, $this->response->getContent());
    }
}
