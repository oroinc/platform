<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Csrf;

use Oro\Bundle\SecurityBundle\Csrf\CookieTokenStorage;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CookieTokenStorageTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var RequestStack|\PHPUnit\Framework\MockObject\MockObject
     */
    private $requestStack;

    /**
     * @var CookieTokenStorage
     */
    private $storage;

    protected function setUp()
    {
        $this->requestStack = $this->createMock(RequestStack::class);

        $this->storage = new CookieTokenStorage($this->requestStack);
        $this->storage->setSecure('auto');
        $this->storage->setHttpOnly(false);
    }

    public function testGetTokenNoRequest()
    {
        $tokenId = 'test';
        $this->requestStack->expects($this->any())
            ->method('getCurrentRequest');

        $this->assertSame('', $this->storage->getToken($tokenId));
    }

    public function testGetTokenNoCookie()
    {
        $tokenId = 'test';
        $request = Request::create('/');

        $this->requestStack->expects($this->any())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $this->assertSame('', $this->storage->getToken($tokenId));
    }

    public function testGetToken()
    {
        $tokenId = 'test';
        $value = 'val';
        $request = Request::create('/');
        $request->cookies->set($tokenId, $value);

        $this->requestStack->expects($this->any())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $this->assertSame($value, $this->storage->getToken($tokenId));
    }

    public function testHasTokenNoRequest()
    {
        $tokenId = 'test';
        $this->requestStack->expects($this->any())
            ->method('getCurrentRequest');

        $this->assertFalse($this->storage->hasToken($tokenId));
    }

    public function testHasTokenNoCookie()
    {
        $tokenId = 'test';
        $request = Request::create('/');

        $this->requestStack->expects($this->any())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $this->assertFalse($this->storage->hasToken($tokenId));
    }

    public function testHasToken()
    {
        $tokenId = 'test';
        $value = 'val';
        $request = Request::create('/');
        $request->cookies->set($tokenId, $value);

        $this->requestStack->expects($this->any())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $this->assertTrue($this->storage->hasToken($tokenId));
    }

    public function testSetToken()
    {
        $tokenId = 'test';
        $value = 'val';
        $request = Request::create('/');

        $this->requestStack->expects($this->any())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $this->storage->setToken($tokenId, $value);

        $cookie = new Cookie($tokenId, $value, 0, '/', null, false, false);
        $this->assertEquals($cookie, $request->attributes->get(CookieTokenStorage::CSRF_COOKIE_ATTRIBUTE));
    }

    public function testSetTokenWithHttpOnlyConfig()
    {
        $tokenId = 'test';
        $value = 'val';
        $request = Request::create('/');

        $this->requestStack->expects($this->any())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $storage = new CookieTokenStorage($this->requestStack);
        $storage->setSecure('auto');
        $storage->setHttpOnly(true);
        $storage->setToken($tokenId, $value);

        $cookie = new Cookie($tokenId, $value, 0, '/', null, false, true);
        $this->assertEquals($cookie, $request->attributes->get(CookieTokenStorage::CSRF_COOKIE_ATTRIBUTE));
    }

    public function testSetTokenWithSecureConfig()
    {
        $tokenId = 'test';
        $value = 'val';
        $request = Request::create('/');

        $this->requestStack->expects($this->any())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $storage = new CookieTokenStorage($this->requestStack);
        $storage->setSecure(true);
        $storage->setHttpOnly(false);
        $storage->setToken($tokenId, $value);

        $cookie = new Cookie($tokenId, $value, 0, '/', null, true, false);
        $this->assertEquals($cookie, $request->attributes->get(CookieTokenStorage::CSRF_COOKIE_ATTRIBUTE));
    }

    public function testSetTokenWithNotSecureConfig()
    {
        $tokenId = 'test';
        $value = 'val';
        $request = Request::create('/');

        $this->requestStack->expects($this->any())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $storage = new CookieTokenStorage($this->requestStack);
        $storage->setSecure(false);
        $storage->setHttpOnly(false);
        $storage->setToken($tokenId, $value);

        $cookie = new Cookie($tokenId, $value, 0, '/', null, false, false);
        $this->assertEquals($cookie, $request->attributes->get(CookieTokenStorage::CSRF_COOKIE_ATTRIBUTE));
    }

    public function testSetTokenWithoutRequest()
    {
        $tokenId = 'test';
        $value = 'val';

        $this->requestStack->expects($this->any())
            ->method('getCurrentRequest')
            ->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->storage->setToken($tokenId, $value);
    }

    public function testRemoveToken()
    {
        $tokenId = 'test';
        $request = Request::create('/');

        $this->requestStack->expects($this->any())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $this->storage->removeToken($tokenId);

        $cookie = new Cookie($tokenId, '', 0, '/', null, $request->isSecure(), false);
        $this->assertEquals($cookie, $request->attributes->get(CookieTokenStorage::CSRF_COOKIE_ATTRIBUTE));
    }
}
