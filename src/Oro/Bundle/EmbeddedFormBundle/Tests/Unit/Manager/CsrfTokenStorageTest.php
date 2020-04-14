<?php

namespace Oro\Bundle\EmbeddedFormBundle\Tests\Unit\Manager;

use Oro\Bundle\EmbeddedFormBundle\Manager\CsrfTokenStorage;
use Oro\Bundle\EmbeddedFormBundle\Manager\SessionIdProviderInterface;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Cache\Simple\PhpFilesCache;

class CsrfTokenStorageTest extends \PHPUnit\Framework\TestCase
{
    const TEST_SESSION_ID          = 'test_sid';
    const TEST_CSRF_TOKEN_ID       = 'test_token_id';
    const TEST_CSRF_TOKEN_LIFETIME = 123;

    /** @var CacheInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $tokenCache;

    /** @var SessionIdProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $sessionIdProvider;

    /** @var CsrfTokenStorage */
    protected $csrfTokenStorage;

    protected function setUp(): void
    {
        $this->tokenCache = $this->createMock(CacheInterface::class);
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

    public function testHasToken()
    {
        $this->tokenCache->expects(self::once())
            ->method('has')
            ->with(self::TEST_CSRF_TOKEN_ID . self::TEST_SESSION_ID)
            ->willReturn(false);

        self::assertFalse(
            $this->csrfTokenStorage->hasToken(self::TEST_CSRF_TOKEN_ID)
        );
    }

    public function testGetTokenShouldReturnNullIfTokenIsNotCached()
    {
        $this->tokenCache->expects(self::once())
            ->method('get')
            ->with(self::TEST_CSRF_TOKEN_ID . self::TEST_SESSION_ID)
            ->willReturn(false);

        self::assertNull(
            $this->csrfTokenStorage->getToken(self::TEST_CSRF_TOKEN_ID)
        );
    }

    public function testGetTokenShouldReturnCachedToken()
    {
        $this->tokenCache->expects(self::once())
            ->method('get')
            ->with(self::TEST_CSRF_TOKEN_ID . self::TEST_SESSION_ID)
            ->willReturn('test');

        self::assertEquals(
            'test',
            $this->csrfTokenStorage->getToken(self::TEST_CSRF_TOKEN_ID)
        );
    }

    public function testSetToken()
    {
        $this->tokenCache->expects(self::once())
            ->method('set')
            ->with(
                self::TEST_CSRF_TOKEN_ID . self::TEST_SESSION_ID,
                'test',
                self::TEST_CSRF_TOKEN_LIFETIME
            );

        $this->csrfTokenStorage->setToken(self::TEST_CSRF_TOKEN_ID, 'test');
    }

    public function testSetTokenWhenPruneable()
    {
        $tokenCache = $this->createMock(PhpFilesCache::class);

        $csrfTokenStorage = new CsrfTokenStorage(
            $tokenCache,
            self::TEST_CSRF_TOKEN_LIFETIME,
            $this->sessionIdProvider
        );

        $tokenCache->expects(self::once())
            ->method('set')
            ->with(
                self::TEST_CSRF_TOKEN_ID . self::TEST_SESSION_ID,
                'test',
                self::TEST_CSRF_TOKEN_LIFETIME
            );

        $tokenCache->expects(self::once())
            ->method('prune');

        $csrfTokenStorage->setToken(self::TEST_CSRF_TOKEN_ID, 'test');
    }

    public function testRemoveToken()
    {
        $this->tokenCache->expects(self::once())
            ->method('delete')
            ->with(self::TEST_CSRF_TOKEN_ID . self::TEST_SESSION_ID);

        $this->csrfTokenStorage->removeToken(self::TEST_CSRF_TOKEN_ID);
    }

    public function testClear()
    {
        $this->tokenCache->expects(self::once())
            ->method('clear');

        $this->csrfTokenStorage->clear();
    }
}
