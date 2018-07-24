<?php

namespace Oro\Bundle\EmbeddedFormBundle\Tests\Unit\Manager;

use Doctrine\Common\Cache\CacheProvider;
use Oro\Bundle\EmbeddedFormBundle\Manager\CsrfTokenStorage;
use Oro\Bundle\EmbeddedFormBundle\Manager\SessionIdProviderInterface;

class CsrfTokenStorageTest extends \PHPUnit\Framework\TestCase
{
    const TEST_SESSION_ID          = 'test_sid';
    const TEST_CSRF_TOKEN_ID       = 'test_token_id';
    const TEST_CSRF_TOKEN_LIFETIME = 123;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $tokenCache;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $sessionIdProvider;

    /** @var CsrfTokenStorage */
    protected $csrfTokenStorage;

    protected function setUp()
    {
        $this->tokenCache = $this->createMock(CacheProvider::class);
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

    public function testHasTokenShouldReturnFalseIfTokenIsNotCached()
    {
        $this->tokenCache->expects(self::once())
            ->method('fetch')
            ->with(self::TEST_CSRF_TOKEN_ID . self::TEST_SESSION_ID)
            ->willReturn(false);

        self::assertFalse(
            $this->csrfTokenStorage->hasToken(self::TEST_CSRF_TOKEN_ID)
        );
    }

    public function testHasTokenShouldReturnTrueIfTokenIsNotCached()
    {
        $this->tokenCache->expects(self::once())
            ->method('fetch')
            ->with(self::TEST_CSRF_TOKEN_ID . self::TEST_SESSION_ID)
            ->willReturn('test');

        self::assertTrue(
            $this->csrfTokenStorage->hasToken(self::TEST_CSRF_TOKEN_ID)
        );
    }

    public function testGetTokenShouldReturnNullIfTokenIsNotCached()
    {
        $this->tokenCache->expects(self::once())
            ->method('fetch')
            ->with(self::TEST_CSRF_TOKEN_ID . self::TEST_SESSION_ID)
            ->willReturn(false);

        self::assertNull(
            $this->csrfTokenStorage->getToken(self::TEST_CSRF_TOKEN_ID)
        );
    }

    public function testGetTokenShouldReturnCachedToken()
    {
        $this->tokenCache->expects(self::once())
            ->method('fetch')
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
            ->method('save')
            ->with(
                self::TEST_CSRF_TOKEN_ID . self::TEST_SESSION_ID,
                'test',
                self::TEST_CSRF_TOKEN_LIFETIME
            );

        $this->csrfTokenStorage->setToken(self::TEST_CSRF_TOKEN_ID, 'test');
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
            ->method('deleteAll');

        $this->csrfTokenStorage->clear();
    }
}
