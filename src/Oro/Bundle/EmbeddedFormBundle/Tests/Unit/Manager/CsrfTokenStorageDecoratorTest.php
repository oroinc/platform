<?php

namespace Oro\Bundle\EmbeddedFormBundle\Tests\Unit\Manager;

use Oro\Bundle\EmbeddedFormBundle\Manager\CsrfTokenStorage;
use Oro\Bundle\EmbeddedFormBundle\Manager\CsrfTokenStorageDecorator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Csrf\TokenStorage\TokenStorageInterface;

class CsrfTokenStorageDecoratorTest extends \PHPUnit\Framework\TestCase
{
    private const TEST_PHP_SESSION_NAME = 'test_php_sid';
    private const TEST_ROUTE_NAME = 'test_route';
    private const TEST_CSRF_TOKEN_ID = 'test_token_id';

    /** @var TokenStorageInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $mainTokenStorage;

    /** @var CsrfTokenStorage|\PHPUnit\Framework\MockObject\MockObject */
    private $embeddedFormTokenStorage;

    /** @var RequestStack|\PHPUnit\Framework\MockObject\MockObject */
    private $requestStack;

    /** @var CsrfTokenStorageDecorator */
    private $csrfTokenStorageDecorator;

    protected function setUp(): void
    {
        $this->mainTokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->embeddedFormTokenStorage = $this->createMock(CsrfTokenStorage::class);
        $this->requestStack = $this->createMock(RequestStack::class);

        $this->csrfTokenStorageDecorator = new CsrfTokenStorageDecorator(
            $this->mainTokenStorage,
            $this->embeddedFormTokenStorage,
            $this->requestStack,
            self::TEST_ROUTE_NAME
        );
    }

    public function testGetTokenForEmptyRequestStack(): void
    {
        $this->requestStack->expects(self::once())
            ->method('getMainRequest')
            ->willReturn(null);

        $this->mainTokenStorage->expects(self::once())
            ->method('getToken')
            ->with(self::TEST_CSRF_TOKEN_ID)
            ->willReturn('test');

        self::assertEquals(
            'test',
            $this->csrfTokenStorageDecorator->getToken(self::TEST_CSRF_TOKEN_ID)
        );
    }

    public function testGetTokenWhenSessionIdCookieExists(): void
    {
        $session = $this->createMock(SessionInterface::class);
        $session->expects(self::once())
            ->method('getName')
            ->willReturn(self::TEST_PHP_SESSION_NAME);

        $request = Request::create('http://test');
        $request->setSession($session);
        $request->attributes->set('_route', self::TEST_ROUTE_NAME);
        $request->cookies->set(self::TEST_PHP_SESSION_NAME, 'php_sid');

        $this->requestStack->expects(self::once())
            ->method('getMainRequest')
            ->willReturn($request);

        $this->mainTokenStorage->expects(self::once())
            ->method('getToken')
            ->with(self::TEST_CSRF_TOKEN_ID)
            ->willReturn('test');

        self::assertEquals(
            'test',
            $this->csrfTokenStorageDecorator->getToken(self::TEST_CSRF_TOKEN_ID)
        );
    }

    public function testGetTokenForNotEmbeddedFormRoute(): void
    {
        $session = $this->createMock(SessionInterface::class);
        $session->expects(self::never())
            ->method('getName');

        $request = Request::create('http://test');
        $request->setSession($session);
        $request->attributes->set('_route', 'not_embedded_form_route');

        $this->requestStack->expects(self::once())
            ->method('getMainRequest')
            ->willReturn($request);

        $this->mainTokenStorage->expects(self::once())
            ->method('getToken')
            ->with(self::TEST_CSRF_TOKEN_ID)
            ->willReturn('test');

        self::assertEquals(
            'test',
            $this->csrfTokenStorageDecorator->getToken(self::TEST_CSRF_TOKEN_ID)
        );
    }

    public function testGetTokenForEmbeddedFormRoute(): void
    {
        $session = $this->createMock(SessionInterface::class);
        $session->expects(self::once())
            ->method('getName')
            ->willReturn(self::TEST_PHP_SESSION_NAME);

        $request = Request::create('http://test');
        $request->setSession($session);
        $request->attributes->set('_route', self::TEST_ROUTE_NAME);

        $this->requestStack->expects(self::once())
            ->method('getMainRequest')
            ->willReturn($request);

        $this->embeddedFormTokenStorage->expects(self::once())
            ->method('getToken')
            ->with(self::TEST_CSRF_TOKEN_ID)
            ->willReturn('test');

        self::assertEquals(
            'test',
            $this->csrfTokenStorageDecorator->getToken(self::TEST_CSRF_TOKEN_ID)
        );
    }

    public function testGetTokenForEmbeddedFormRouteButWithSessionIdCookie(): void
    {
        $session = $this->createMock(SessionInterface::class);
        $session->expects(self::once())
            ->method('getName')
            ->willReturn(self::TEST_PHP_SESSION_NAME);

        $request = Request::create('http://test');
        $request->setSession($session);
        $request->attributes->set('_route', self::TEST_ROUTE_NAME);
        $request->cookies->set(self::TEST_PHP_SESSION_NAME, 'php_sid');

        $this->requestStack->expects(self::once())
            ->method('getMainRequest')
            ->willReturn($request);

        $this->mainTokenStorage->expects(self::once())
            ->method('getToken')
            ->with(self::TEST_CSRF_TOKEN_ID)
            ->willReturn('test');

        self::assertEquals(
            'test',
            $this->csrfTokenStorageDecorator->getToken(self::TEST_CSRF_TOKEN_ID)
        );
    }

    public function testHasToken(): void
    {
        $session = $this->createMock(SessionInterface::class);
        $session->expects(self::once())
            ->method('getName')
            ->willReturn(self::TEST_PHP_SESSION_NAME);

        $request = Request::create('http://test');
        $request->setSession($session);
        $request->attributes->set('_route', self::TEST_ROUTE_NAME);

        $this->requestStack->expects(self::once())
            ->method('getMainRequest')
            ->willReturn($request);

        $this->embeddedFormTokenStorage->expects(self::once())
            ->method('hasToken')
            ->with(self::TEST_CSRF_TOKEN_ID)
            ->willReturn(true);

        self::assertTrue(
            $this->csrfTokenStorageDecorator->hasToken(self::TEST_CSRF_TOKEN_ID)
        );
    }

    public function testSetToken(): void
    {
        $session = $this->createMock(SessionInterface::class);
        $session->expects(self::once())
            ->method('getName')
            ->willReturn(self::TEST_PHP_SESSION_NAME);

        $request = Request::create('http://test');
        $request->setSession($session);
        $request->attributes->set('_route', self::TEST_ROUTE_NAME);

        $this->requestStack->expects(self::once())
            ->method('getMainRequest')
            ->willReturn($request);

        $this->embeddedFormTokenStorage->expects(self::once())
            ->method('setToken')
            ->with(self::TEST_CSRF_TOKEN_ID, 'test');

        $this->csrfTokenStorageDecorator->setToken(self::TEST_CSRF_TOKEN_ID, 'test');
    }

    public function testRemoveToken(): void
    {
        $session = $this->createMock(SessionInterface::class);
        $session->expects(self::once())
            ->method('getName')
            ->willReturn(self::TEST_PHP_SESSION_NAME);

        $request = Request::create('http://test');
        $request->setSession($session);
        $request->attributes->set('_route', self::TEST_ROUTE_NAME);

        $this->requestStack->expects(self::once())
            ->method('getMainRequest')
            ->willReturn($request);

        $this->embeddedFormTokenStorage->expects(self::once())
            ->method('removeToken')
            ->with(self::TEST_CSRF_TOKEN_ID);

        $this->csrfTokenStorageDecorator->removeToken(self::TEST_CSRF_TOKEN_ID);
    }

    public function testClear(): void
    {
        $session = $this->createMock(SessionInterface::class);
        $session->expects(self::once())
            ->method('getName')
            ->willReturn(self::TEST_PHP_SESSION_NAME);

        $request = Request::create('http://test');
        $request->setSession($session);
        $request->attributes->set('_route', self::TEST_ROUTE_NAME);

        $this->requestStack->expects(self::once())
            ->method('getMainRequest')
            ->willReturn($request);

        $this->embeddedFormTokenStorage->expects(self::once())
            ->method('clear');

        $this->csrfTokenStorageDecorator->clear();
    }
}
