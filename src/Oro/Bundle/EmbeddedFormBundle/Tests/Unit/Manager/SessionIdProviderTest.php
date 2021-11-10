<?php

namespace Oro\Bundle\EmbeddedFormBundle\Tests\Unit\Manager;

use Oro\Bundle\EmbeddedFormBundle\Manager\SessionIdProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class SessionIdProviderTest extends \PHPUnit\Framework\TestCase
{
    private const TEST_SESSION_FIELD_NAME = 'test_session_field';

    /** @var RequestStack|\PHPUnit\Framework\MockObject\MockObject */
    private $requestStack;

    /** @var SessionIdProvider */
    private $sessionIdProvider;

    protected function setUp(): void
    {
        $this->requestStack = $this->createMock(RequestStack::class);

        $this->sessionIdProvider = new SessionIdProvider(
            $this->requestStack,
            self::TEST_SESSION_FIELD_NAME
        );
    }

    public function testShouldReturnNullIfNoRequestsInStack(): void
    {
        $this->requestStack->expects(self::once())
            ->method('getMainRequest')
            ->willReturn(null);

        self::assertNull($this->sessionIdProvider->getSessionId());
    }

    public function testShouldReturnNullIfNoSessionIdInPost(): void
    {
        $request = Request::create('http://test', 'POST');

        $this->requestStack->expects(self::once())
            ->method('getMainRequest')
            ->willReturn($request);

        self::assertNull($this->sessionIdProvider->getSessionId());
    }

    public function testShouldReturnSessionIdFromPostField(): void
    {
        $request = Request::create('http://test', 'POST');
        $request->request->set(self::TEST_SESSION_FIELD_NAME, 'test_sid');

        $this->requestStack->expects(self::once())
            ->method('getMainRequest')
            ->willReturn($request);

        self::assertEquals('test_sid', $this->sessionIdProvider->getSessionId());
    }

    public function testShouldGenerateSessionIdForGetMethod(): void
    {
        $request = Request::create('http://test');

        $this->requestStack->expects(self::once())
            ->method('getMainRequest')
            ->willReturn($request);

        self::assertNotEmpty($this->sessionIdProvider->getSessionId());
    }

    public function testShouldRememberGeneratedSessionIdForGetMethod(): void
    {
        $request = Request::create('http://test');

        $this->requestStack->expects(self::exactly(2))
            ->method('getMainRequest')
            ->willReturn($request);

        $sessionId = $this->sessionIdProvider->getSessionId();
        self::assertNotEmpty($sessionId);
        self::assertEquals($sessionId, $this->sessionIdProvider->getSessionId());
    }
}
