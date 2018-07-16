<?php

namespace Oro\Bundle\EmbeddedFormBundle\Tests\Unit\Manager;

use Oro\Bundle\EmbeddedFormBundle\Manager\SessionIdProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class SessionIdProviderTest extends \PHPUnit\Framework\TestCase
{
    const TEST_SESSION_FIELD_NAME = 'test_session_field';

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $requestStack;

    /** @var SessionIdProvider */
    protected $sessionIdProvider;

    protected function setUp()
    {
        $this->requestStack = $this->getMockBuilder(RequestStack::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->sessionIdProvider = new SessionIdProvider(
            $this->requestStack,
            self::TEST_SESSION_FIELD_NAME
        );
    }

    public function testShouldReturnNullIfNoRequestsInStack()
    {
        $this->requestStack->expects(self::once())
            ->method('getMasterRequest')
            ->willReturn(null);

        self::assertNull($this->sessionIdProvider->getSessionId());
    }

    public function testShouldReturnNullIfNoSessionIdInPost()
    {
        $request = Request::create('http://test', 'POST');

        $this->requestStack->expects(self::once())
            ->method('getMasterRequest')
            ->willReturn($request);

        self::assertNull($this->sessionIdProvider->getSessionId());
    }

    public function testShouldReturnSessionIdFromPostField()
    {
        $request = Request::create('http://test', 'POST');
        $request->request->set(self::TEST_SESSION_FIELD_NAME, 'test_sid');

        $this->requestStack->expects(self::once())
            ->method('getMasterRequest')
            ->willReturn($request);

        self::assertEquals('test_sid', $this->sessionIdProvider->getSessionId());
    }

    public function testShouldGenerateSessionIdForGetMethod()
    {
        $request = Request::create('http://test', 'GET');

        $this->requestStack->expects(self::once())
            ->method('getMasterRequest')
            ->willReturn($request);

        self::assertNotEmpty($this->sessionIdProvider->getSessionId());
    }

    public function testShouldRememberGeneratedSessionIdForGetMethod()
    {
        $request = Request::create('http://test', 'GET');

        $this->requestStack->expects(self::exactly(2))
            ->method('getMasterRequest')
            ->willReturn($request);

        $sessionId = $this->sessionIdProvider->getSessionId();
        self::assertNotEmpty($sessionId);
        self::assertEquals($sessionId, $this->sessionIdProvider->getSessionId());
    }
}
