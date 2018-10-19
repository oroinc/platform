<?php

namespace Oro\Bundle\EmbeddedFormBundle\Tests\Unit\Manager;

use Oro\Bundle\EmbeddedFormBundle\Manager\CsrfTokenStorage;
use Oro\Bundle\EmbeddedFormBundle\Manager\CsrfTokenStorageDecorator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Csrf\TokenStorage\TokenStorageInterface;

class CsrfTokenStorageDecoratorTest extends \PHPUnit\Framework\TestCase
{
    const TEST_SESSION_FIELD_NAME = '_test_sid';
    const TEST_PHP_SESSION_NAME   = 'test_php_sid';
    const TEST_ROUTE_NAME         = 'test_route';
    const TEST_CSRF_TOKEN_ID      = 'test_token_id';

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $mainTokenStorage;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $embeddedFormTokenStorage;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $requestStack;

    /** @var CsrfTokenStorageDecorator */
    protected $csrfTokenStorageDecorator;

    protected function setUp()
    {
        $this->mainTokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->embeddedFormTokenStorage = $this->createMock(CsrfTokenStorage::class);
        $this->requestStack = $this->createMock(RequestStack::class);

        $this->csrfTokenStorageDecorator = new CsrfTokenStorageDecorator(
            $this->mainTokenStorage,
            $this->embeddedFormTokenStorage,
            $this->requestStack,
            ['name' => self::TEST_PHP_SESSION_NAME],
            self::TEST_ROUTE_NAME,
            self::TEST_SESSION_FIELD_NAME
        );
    }

    public function testGetTokenForEmptyRequestStack()
    {
        $this->requestStack->expects(self::once())
            ->method('getMasterRequest')
            ->willReturn(null);

        $this->mainTokenStorage->expects(self::once())
            ->method('getToken')
            ->with(self::TEST_CSRF_TOKEN_ID)
            ->willReturn('test');

        $this->assertEquals(
            'test',
            $this->csrfTokenStorageDecorator->getToken(self::TEST_CSRF_TOKEN_ID)
        );
    }

    public function testGetTokenWhenSessionIdCookieExists()
    {
        $request = Request::create('http://test');
        $request->cookies->set(self::TEST_PHP_SESSION_NAME, 'php_sid');

        $this->requestStack->expects(self::once())
            ->method('getMasterRequest')
            ->willReturn($request);

        $this->mainTokenStorage->expects(self::once())
            ->method('getToken')
            ->with(self::TEST_CSRF_TOKEN_ID)
            ->willReturn('test');

        $this->assertEquals(
            'test',
            $this->csrfTokenStorageDecorator->getToken(self::TEST_CSRF_TOKEN_ID)
        );
    }

    public function testGetTokenForNotEmbeddedFormRoute()
    {
        $request = Request::create('http://test');
        $request->attributes->set('_route', 'not_embedded_form_route');

        $this->requestStack->expects(self::once())
            ->method('getMasterRequest')
            ->willReturn($request);

        $this->mainTokenStorage->expects(self::once())
            ->method('getToken')
            ->with(self::TEST_CSRF_TOKEN_ID)
            ->willReturn('test');

        $this->assertEquals(
            'test',
            $this->csrfTokenStorageDecorator->getToken(self::TEST_CSRF_TOKEN_ID)
        );
    }

    public function testGetTokenForEmbeddedFormRoute()
    {
        $request = Request::create('http://test');
        $request->attributes->set('_route', self::TEST_ROUTE_NAME);

        $this->requestStack->expects(self::once())
            ->method('getMasterRequest')
            ->willReturn($request);

        $this->embeddedFormTokenStorage->expects(self::once())
            ->method('getToken')
            ->with(self::TEST_CSRF_TOKEN_ID)
            ->willReturn('test');

        $this->assertEquals(
            'test',
            $this->csrfTokenStorageDecorator->getToken(self::TEST_CSRF_TOKEN_ID)
        );
    }

    public function testGetTokenForEmbeddedFormRouteButWithSessionIdCookie()
    {
        $request = Request::create('http://test');
        $request->attributes->set('_route', self::TEST_ROUTE_NAME);
        $request->cookies->set(self::TEST_PHP_SESSION_NAME, 'php_sid');

        $this->requestStack->expects(self::once())
            ->method('getMasterRequest')
            ->willReturn($request);

        $this->mainTokenStorage->expects(self::once())
            ->method('getToken')
            ->with(self::TEST_CSRF_TOKEN_ID)
            ->willReturn('test');

        $this->assertEquals(
            'test',
            $this->csrfTokenStorageDecorator->getToken(self::TEST_CSRF_TOKEN_ID)
        );
    }

    public function testHasToken()
    {
        $request = Request::create('http://test');
        $request->attributes->set('_route', self::TEST_ROUTE_NAME);

        $this->requestStack->expects(self::once())
            ->method('getMasterRequest')
            ->willReturn($request);

        $this->embeddedFormTokenStorage->expects(self::once())
            ->method('hasToken')
            ->with(self::TEST_CSRF_TOKEN_ID)
            ->willReturn(true);

        $this->assertTrue(
            $this->csrfTokenStorageDecorator->hasToken(self::TEST_CSRF_TOKEN_ID)
        );
    }

    public function testSetToken()
    {
        $request = Request::create('http://test');
        $request->attributes->set('_route', self::TEST_ROUTE_NAME);

        $this->requestStack->expects(self::once())
            ->method('getMasterRequest')
            ->willReturn($request);

        $this->embeddedFormTokenStorage->expects(self::once())
            ->method('setToken')
            ->with(self::TEST_CSRF_TOKEN_ID, 'test');

        $this->csrfTokenStorageDecorator->setToken(self::TEST_CSRF_TOKEN_ID, 'test');
    }

    public function testRemoveToken()
    {
        $request = Request::create('http://test');
        $request->attributes->set('_route', self::TEST_ROUTE_NAME);

        $this->requestStack->expects(self::once())
            ->method('getMasterRequest')
            ->willReturn($request);

        $this->embeddedFormTokenStorage->expects(self::once())
            ->method('removeToken')
            ->with(self::TEST_CSRF_TOKEN_ID);

        $this->csrfTokenStorageDecorator->removeToken(self::TEST_CSRF_TOKEN_ID);
    }

    public function testClear()
    {
        $request = Request::create('http://test');
        $request->attributes->set('_route', self::TEST_ROUTE_NAME);

        $this->requestStack->expects(self::once())
            ->method('getMasterRequest')
            ->willReturn($request);

        $this->embeddedFormTokenStorage->expects(self::once())
            ->method('clear');

        $this->csrfTokenStorageDecorator->clear();
    }
}
