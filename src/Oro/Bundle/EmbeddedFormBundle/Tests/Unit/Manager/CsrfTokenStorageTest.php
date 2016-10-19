<?php

namespace Oro\Bundle\EmbeddedFormBundle\Tests\Unit\Manager;

use Doctrine\Common\Cache\Cache;
use Oro\Bundle\EmbeddedFormBundle\Manager\CsrfTokenStorage;
use Oro\Bundle\EmbeddedFormBundle\Manager\SessionIdProviderInterface;

class CsrfTokenStorageTest extends \PHPUnit_Framework_TestCase
{
    const TEST_SESSION_ID          = 'test_sid';
    const TEST_CSRF_TOKEN_ID       = 'test_token_id';
    const TEST_CSRF_TOKEN_LIFETIME = 123;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $tokenCache;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $sessionIdProvider;

    /** @var CsrfTokenStorage */
    protected $csrfTokenStorage;

    protected function setUp()
    {
        $this->tokenCache = $this->getMock(Cache::class);
        $this->sessionIdProvider = $this->getMock(SessionIdProviderInterface::class);

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
}
