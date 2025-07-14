<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Csrf;

use Oro\Bundle\SecurityBundle\Csrf\CookieTokenStorage;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CookieTokenStorageTest extends TestCase
{
    private RequestStack&MockObject $requestStack;
    private CookieTokenStorage $storage;

    #[\Override]
    protected function setUp(): void
    {
        $this->requestStack = $this->createMock(RequestStack::class);

        $this->storage = new CookieTokenStorage('auto', $this->requestStack, Cookie::SAMESITE_STRICT);
    }

    public function testGetTokenNoRequest(): void
    {
        $tokenId = 'test';
        $this->requestStack->expects($this->any())
            ->method('getCurrentRequest');

        $this->assertSame('', $this->storage->getToken($tokenId));
    }

    public function testGetTokenNoCookie(): void
    {
        $tokenId = 'test';
        $request = Request::create('/');

        $this->requestStack->expects($this->any())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $this->assertSame('', $this->storage->getToken($tokenId));
    }

    public function testGetToken(): void
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

    public function testHasTokenNoRequest(): void
    {
        $tokenId = 'test';
        $this->requestStack->expects($this->any())
            ->method('getCurrentRequest');

        $this->assertFalse($this->storage->hasToken($tokenId));
    }

    public function testHasTokenNoCookie(): void
    {
        $tokenId = 'test';
        $request = Request::create('/');

        $this->requestStack->expects($this->any())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $this->assertFalse($this->storage->hasToken($tokenId));
    }

    public function testHasToken(): void
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

    public function testSetToken(): void
    {
        $tokenId = 'test';
        $value = 'val';
        $request = Request::create('/');

        $this->requestStack->expects($this->any())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $this->storage->setToken($tokenId, $value);

        $cookie = new Cookie($tokenId, $value, 0, '/', null, null, false, false, Cookie::SAMESITE_STRICT);
        $this->assertEquals($cookie, $request->attributes->get(CookieTokenStorage::CSRF_COOKIE_ATTRIBUTE));
    }

    public function testSetTokenWithSecureConfig(): void
    {
        $tokenId = 'test';
        $value = 'val';
        $request = Request::create('/');

        $this->requestStack->expects($this->any())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $storage = new CookieTokenStorage(true, $this->requestStack, Cookie::SAMESITE_STRICT);
        $storage->setToken($tokenId, $value);

        $cookie = new Cookie($tokenId, $value, 0, '/', null, true, false, false, Cookie::SAMESITE_STRICT);
        $this->assertEquals($cookie, $request->attributes->get(CookieTokenStorage::CSRF_COOKIE_ATTRIBUTE));
    }

    public function testSetTokenWithNotSecureConfig(): void
    {
        $tokenId = 'test';
        $value = 'val';
        $request = Request::create('/');

        $this->requestStack->expects($this->any())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $storage = new CookieTokenStorage(false, $this->requestStack, Cookie::SAMESITE_STRICT);
        $storage->setToken($tokenId, $value);

        $cookie = new Cookie($tokenId, $value, 0, '/', null, false, false, false, Cookie::SAMESITE_STRICT);
        $this->assertEquals($cookie, $request->attributes->get(CookieTokenStorage::CSRF_COOKIE_ATTRIBUTE));
    }

    public function testSetTokenWithoutRequest(): void
    {
        $tokenId = 'test';
        $value = 'val';

        $this->requestStack->expects($this->any())
            ->method('getCurrentRequest')
            ->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->storage->setToken($tokenId, $value);
    }

    public function testRemoveToken(): void
    {
        $tokenId = 'test';
        $request = Request::create('/');

        $this->requestStack->expects($this->any())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $this->storage->removeToken($tokenId);

        $cookie = new Cookie($tokenId, '', 0, '/', null, $request->isSecure(), false, false, Cookie::SAMESITE_STRICT);
        $this->assertEquals($cookie, $request->attributes->get(CookieTokenStorage::CSRF_COOKIE_ATTRIBUTE));
    }
}
