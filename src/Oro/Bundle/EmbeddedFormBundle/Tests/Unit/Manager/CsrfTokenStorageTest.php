<?php

namespace Oro\Bundle\EmbeddedFormBundle\Tests\Unit\Manager;

use Oro\Bundle\EmbeddedFormBundle\Manager\CsrfTokenStorage;
use Oro\Bundle\EmbeddedFormBundle\Manager\SessionIdProviderInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\PhpFilesAdapter;
use Symfony\Component\Cache\CacheItem;
use Symfony\Contracts\Cache\ItemInterface;

class CsrfTokenStorageTest extends \PHPUnit\Framework\TestCase
{
    private const TEST_SESSION_ID = 'test_sid';
    private const TEST_CSRF_TOKEN_ID = 'test_token_id';
    private const TEST_CSRF_TOKEN_LIFETIME = 123;

    /** @var ArrayAdapter */
    private $tokenCache;

    /** @var SessionIdProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $sessionIdProvider;

    /** @var CsrfTokenStorage */
    private $csrfTokenStorage;

    protected function setUp(): void
    {
        $this->tokenCache = new ArrayAdapter();
        $this->sessionIdProvider = $this->createMock(SessionIdProviderInterface::class);

        $this->sessionIdProvider->expects(self::any())
            ->method('getSessionId')
            ->willReturn(self::TEST_SESSION_ID);

        $this->csrfTokenStorage = new CsrfTokenStorage(
            $this->tokenCache,
            self::TEST_CSRF_TOKEN_LIFETIME,
            $this->sessionIdProvider
        );
    }

    public function testHasToken(): void
    {
        self::assertFalse(
            $this->csrfTokenStorage->hasToken(self::TEST_CSRF_TOKEN_ID)
        );
    }

    public function testGetTokenShouldReturnNullIfTokenIsNotCached(): void
    {
        self::assertNull(
            $this->csrfTokenStorage->getToken(self::TEST_CSRF_TOKEN_ID)
        );
    }

    public function testGetTokenShouldReturnCachedToken(): void
    {
        $this->tokenCache->get(self::TEST_CSRF_TOKEN_ID . self::TEST_SESSION_ID, fn (ItemInterface $item) => 'test');

        self::assertEquals(
            'test',
            $this->csrfTokenStorage->getToken(self::TEST_CSRF_TOKEN_ID)
        );
    }

    public function testSetToken(): void
    {
        $this->assertSavedToken();
    }

    public function testSetTokenWhenPruneable(): void
    {
        $tokenCache = $this->createMock(PhpFilesAdapter::class);

        $csrfTokenStorage = new CsrfTokenStorage(
            $tokenCache,
            self::TEST_CSRF_TOKEN_LIFETIME,
            $this->sessionIdProvider
        );

        $cacheItem = new CacheItem();
        $tokenCache->expects(self::once())
            ->method('getItem')
            ->with(self::TEST_CSRF_TOKEN_ID . self::TEST_SESSION_ID)
            ->willReturn($cacheItem);
        $tokenCache->expects(self::once())
            ->method('save');
        $tokenCache->expects(self::once())
            ->method('prune');

        $csrfTokenStorage->setToken(self::TEST_CSRF_TOKEN_ID, 'test');
    }

    public function testRemoveToken(): void
    {
        $this->assertSavedToken();

        $this->csrfTokenStorage->removeToken(self::TEST_CSRF_TOKEN_ID);

        self::assertNull($this->csrfTokenStorage->getToken(self::TEST_CSRF_TOKEN_ID));
    }

    public function testClear(): void
    {
        $this->assertSavedToken();

        $this->csrfTokenStorage->clear();

        self::assertNull($this->csrfTokenStorage->getToken(self::TEST_CSRF_TOKEN_ID));
    }

    private function assertSavedToken(): void
    {
        $token = 'test';
        $this->csrfTokenStorage->setToken(self::TEST_CSRF_TOKEN_ID, $token);

        self::assertEquals($token, $this->csrfTokenStorage->getToken(self::TEST_CSRF_TOKEN_ID));
    }
}
