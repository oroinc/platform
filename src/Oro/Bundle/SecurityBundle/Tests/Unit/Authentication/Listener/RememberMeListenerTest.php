<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Authentication\Listener;

use Oro\Bundle\SecurityBundle\Authentication\Listener\RememberMeListener;
use Oro\Bundle\SecurityBundle\Csrf\CsrfRequestManager;
use Oro\Bundle\SecurityBundle\Request\CsrfProtectedRequestHelper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Http\Authenticator\RememberMeAuthenticator;

class RememberMeListenerTest extends \PHPUnit\Framework\TestCase
{
    private const SESSION_NAME = 'TEST_SESSION_ID';
    private const SESSION_ID = 'o595fqdg5214u4e4nfcs3uc923';

    private RememberMeAuthenticator $innerRememberMeAuthenticator;
    private CsrfProtectedRequestHelper $csrfProtectedRequestHelper;
    private RememberMeListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->innerRememberMeAuthenticator = $this->createMock(RememberMeAuthenticator::class);
        $this->csrfProtectedRequestHelper = $this->createMock(CsrfProtectedRequestHelper::class);
        $this->listener = new RememberMeListener(
            $this->innerRememberMeAuthenticator,
            $this->csrfProtectedRequestHelper,
            true // Assuming CSRF protected Ajax only mode is turned on for testing
        );
    }

    public function testSupportsCsrfProtectedAjaxRequest(): void
    {
        $request = $this->createRequestWithCsrfToken('XMLHttpRequest');
        $this->csrfProtectedRequestHelper->method('isCsrfProtectedRequest')
            ->willReturn(true);

        $this->innerRememberMeAuthenticator->expects(self::once())
            ->method('supports')
            ->with($request)
            ->willReturn(true);

        $this->assertTrue($this->listener->supports($request));
    }

    public function testDoesNotSupportNonCsrfProtectedAjaxRequest(): void
    {
        $request = $this->createRequestWithCsrfToken('XMLHttpRequest');
        $this->csrfProtectedRequestHelper->method('isCsrfProtectedRequest')
            ->willReturn(false);

        $this->assertFalse($this->listener->supports($request));
    }

    public function testAuthenticate(): void
    {
        $event = $this->createMainRequestEvent(true);
        $request = $event->getRequest();
        $request->cookies->add([self::SESSION_NAME => self::SESSION_ID]);

        $this->innerRememberMeAuthenticator->expects(self::once())
            ->method('authenticate')
            ->with($request);

        $this->listener->authenticate($event);
    }

    private function createRequestWithCsrfToken(string $requestType): Request
    {
        $request = new Request();
        $request->cookies->add([self::SESSION_NAME => self::SESSION_ID]);
        $request->headers->add([CsrfRequestManager::CSRF_HEADER => '_stub_value']);
        $request->setMethod($requestType === 'XMLHttpRequest' ? 'POST' : 'GET');
        if ($requestType === 'XMLHttpRequest') {
            $request->headers->set('X-Requested-With', 'XMLHttpRequest');
        }
        return $request;
    }

    private function createMainRequestEvent(bool $isXmlHttpRequest = false): RequestEvent
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = new Request([], [], ['_route' => 'foo']);
        $session = $this->createMock(SessionInterface::class);
        $session->expects(self::any())
            ->method('getName')
            ->willReturn(self::SESSION_NAME);
        $request->setSession($session);

        if ($isXmlHttpRequest) {
            $request->headers->set('X-Requested-With', 'XMLHttpRequest');
        }

        return new RequestEvent(
            $kernel,
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );
    }
}
