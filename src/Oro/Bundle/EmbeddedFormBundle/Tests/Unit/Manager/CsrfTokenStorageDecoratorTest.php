<?php

namespace Oro\Bundle\EmbeddedFormBundle\Tests\Unit\Manager;

use Oro\Bundle\EmbeddedFormBundle\Manager\CsrfTokenStorageDecorator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Csrf\TokenStorage\TokenStorageInterface;

class CsrfTokenStorageDecoratorTest extends \PHPUnit_Framework_TestCase
{
    const TEST_SESSION_FIELD_NAME = '_test_sid';
    const TEST_PHP_SESSION_NAME   = 'test_php_sid';
    const TEST_URL_PREFIX         = '/embedded/form/';
    const TEST_CSRF_TOKEN_ID      = 'test_token_id';

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $mainTokenStorage;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $embeddedFormTokenStorage;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $requestStack;

    /** @var CsrfTokenStorageDecorator */
    protected $csrfTokenStorageDecorator;

    protected function setUp()
    {
        $this->mainTokenStorage = $this->getMock(TokenStorageInterface::class);
        $this->embeddedFormTokenStorage = $this->getMock(TokenStorageInterface::class);
        $this->requestStack = $this->getMock(RequestStack::class);

        $this->csrfTokenStorageDecorator = new CsrfTokenStorageDecorator(
            $this->mainTokenStorage,
            $this->embeddedFormTokenStorage,
            $this->requestStack,
            ['name' => self::TEST_PHP_SESSION_NAME],
            self::TEST_URL_PREFIX,
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

    /**
     * @dataProvider notEmbeddedFormPathProvider
     */
    public function testGetTokenForNotEmbeddedFormUrl($path)
    {
        $request = Request::create('http://test');
        $request->server->set('PATH_INFO', $path);

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

    public function notEmbeddedFormPathProvider()
    {
        return [
            [''],
            ['/page'],
            ['/embedded'],
            ['/embedded/'],
            ['/embedded/form'],
            ['/page/embedded/form/123'],
        ];
    }

    /**
     * @dataProvider embeddedFormPathProvider
     */
    public function testGetTokenForEmbeddedFormUrl($path)
    {
        $request = Request::create('http://test');
        $request->server->set('PATH_INFO', $path);

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

    public function embeddedFormPathProvider()
    {
        return [
            ['/embedded/form/'],
            ['/embedded/form/123'],
        ];
    }

    public function testGetTokenForEmbeddedFormUrlButWithSessionIdCookir()
    {
        $request = Request::create('http://test');
        $request->server->set('PATH_INFO', '/embedded/form/123');
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
        $request->server->set('PATH_INFO', '/embedded/form/123');

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
        $request->server->set('PATH_INFO', '/embedded/form/123');

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
        $request->server->set('PATH_INFO', '/embedded/form/123');

        $this->requestStack->expects(self::once())
            ->method('getMasterRequest')
            ->willReturn($request);

        $this->embeddedFormTokenStorage->expects(self::once())
            ->method('removeToken')
            ->with(self::TEST_CSRF_TOKEN_ID);

        $this->csrfTokenStorageDecorator->removeToken(self::TEST_CSRF_TOKEN_ID);
    }
}
