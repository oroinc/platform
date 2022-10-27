<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Request;

use Oro\Bundle\SecurityBundle\Csrf\CsrfRequestManager;
use Oro\Bundle\SecurityBundle\Request\CsrfProtectedRequestHelper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class CsrfProtectedRequestHelperTest extends \PHPUnit\Framework\TestCase
{
    /** @var CsrfRequestManager|\PHPUnit\Framework\MockObject\MockObject */
    private $csrfRequestManager;

    /** @var CsrfProtectedRequestHelper */
    private $csrfProtectedRequestHelper;

    protected function setUp(): void
    {
        $this->csrfRequestManager = $this->createMock(CsrfRequestManager::class);

        $this->csrfProtectedRequestHelper = new CsrfProtectedRequestHelper($this->csrfRequestManager);
    }

    public function testIsCsrfProtectedRequestForCsrfProtectedGetRequest(): void
    {
        $request = Request::create('http://test.com');
        $request->cookies->add(['TEST_SESSION_ID' => 'some session id']);
        $request->headers->set('X-CSRF-Header', 'test');
        $session = $this->createMock(SessionInterface::class);
        $session->expects(self::once())
            ->method('getName')
            ->willReturn('TEST_SESSION_ID');
        $request->setSession($session);

        $this->csrfRequestManager->expects(self::never())
            ->method('isRequestTokenValid');

        self::assertTrue($this->csrfProtectedRequestHelper->isCsrfProtectedRequest($request));
    }

    public function testIsCsrfProtectedRequestForCsrfProtectedNotGetRequest(): void
    {
        $request = Request::create('http://test.com', 'POST');
        $request->cookies->add(['TEST_SESSION_ID' => 'some session id']);
        $session = $this->createMock(SessionInterface::class);
        $session->expects(self::once())
            ->method('getName')
            ->willReturn('TEST_SESSION_ID');
        $request->setSession($session);

        $this->csrfRequestManager->expects(self::once())
            ->method('isRequestTokenValid')
            ->with(self::identicalTo($request))
            ->willReturn(true);

        self::assertTrue($this->csrfProtectedRequestHelper->isCsrfProtectedRequest($request));
    }

    public function testIsCsrfProtectedRequestForNotCsrfProtectedGetRequest(): void
    {
        $request = Request::create('http://test.com');
        $request->cookies->add(['TEST_SESSION_ID' => 'some session id']);
        $session = $this->createMock(SessionInterface::class);
        $session->expects(self::once())
            ->method('getName')
            ->willReturn('TEST_SESSION_ID');
        $request->setSession($session);

        $this->csrfRequestManager->expects(self::never())
            ->method('isRequestTokenValid');

        self::assertFalse($this->csrfProtectedRequestHelper->isCsrfProtectedRequest($request));
    }

    public function testIsCsrfProtectedRequestForNotCsrfProtectedNotGetRequest(): void
    {
        $request = Request::create('http://test.com', 'POST');
        $request->cookies->add(['TEST_SESSION_ID' => 'some session id']);
        $session = $this->createMock(SessionInterface::class);
        $session->expects(self::once())
            ->method('getName')
            ->willReturn('TEST_SESSION_ID');
        $request->setSession($session);

        $this->csrfRequestManager->expects(self::once())
            ->method('isRequestTokenValid')
            ->with(self::identicalTo($request))
            ->willReturn(false);

        self::assertFalse($this->csrfProtectedRequestHelper->isCsrfProtectedRequest($request));
    }

    public function testIsCsrfProtectedRequestForRequestWithoutSessionCookie(): void
    {
        $request = Request::create('http://test.com');
        $request->headers->set('X-CSRF-Header', 'test');
        $session = $this->createMock(SessionInterface::class);
        $session->expects(self::once())
            ->method('getName')
            ->willReturn('TEST_SESSION_ID');
        $request->setSession($session);

        $this->csrfRequestManager->expects(self::never())
            ->method('isRequestTokenValid');

        self::assertFalse($this->csrfProtectedRequestHelper->isCsrfProtectedRequest($request));
    }

    public function testIsCsrfProtectedRequestForStatelessRequest(): void
    {
        $request = Request::create('http://test.com');
        $request->headers->set('X-CSRF-Header', 'test');

        $this->csrfRequestManager->expects(self::never())
            ->method('isRequestTokenValid');

        self::assertFalse($this->csrfProtectedRequestHelper->isCsrfProtectedRequest($request));
    }
}
