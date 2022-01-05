<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Authentication\Listener;

use Oro\Bundle\SecurityBundle\Authentication\Listener\RememberMeListener;
use Oro\Bundle\SecurityBundle\Csrf\CsrfRequestManager;
use Oro\Bundle\SecurityBundle\Request\CsrfProtectedRequestHelper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Http\Firewall\RememberMeListener as OrigRememberMeListener;

class RememberMeListenerTest extends \PHPUnit\Framework\TestCase
{
    private const SESSION_NAME = 'TEST_SESSION_ID';
    private const SESSION_ID = 'o595fqdg5214u4e4nfcs3uc923';

    public function testShouldCallInnerAuthenticateForAnyRequestWithAjaxCsrfModeOff(): void
    {
        $event = $this->createMasterRequestEvent();

        $innerListener = $this->createMock(OrigRememberMeListener::class);
        $csrfRequestManager = $this->createMock(CsrfRequestManager::class);

        $innerListener->expects(self::once())
            ->method('authenticate')
            ->with($event);
        $csrfRequestManager->expects(self::never())
            ->method('isRequestTokenValid')
            ->with($event->getRequest(), false);

        $listener = $this->getListener($innerListener);
        $listener->setCsrfProtectedRequestHelper(new CsrfProtectedRequestHelper($csrfRequestManager));

        $listener($event);
    }

    public function testShouldCallInnerAuthenticateForGetCsrfProtectedRequest(): void
    {
        $event = $this->createMasterRequestEvent();
        $event->getRequest()->cookies->add([self::SESSION_NAME => self::SESSION_ID]);
        $event->getRequest()->headers->add([CsrfRequestManager::CSRF_HEADER => '_stub_value']);
        $event->getRequest()->setMethod('GET');

        $innerListener = $this->createMock(OrigRememberMeListener::class);
        $csrfRequestManager = $this->createMock(CsrfRequestManager::class);

        $innerListener->expects(self::once())
            ->method('authenticate')
            ->with($event);
        $csrfRequestManager->expects(self::never())
            ->method('isRequestTokenValid')
            ->with($event->getRequest(), false);

        $listener = $this->getListener($innerListener);
        $listener->setCsrfProtectedRequestHelper(new CsrfProtectedRequestHelper($csrfRequestManager));
        $listener->switchToProcessAjaxCsrfOnlyRequest();

        $listener($event);
    }

    public function testShouldCallInnerAuthenticateForPostCsrfProtectedRequest(): void
    {
        $event = $this->createMasterRequestEvent();
        $event->getRequest()->cookies->add([self::SESSION_NAME => self::SESSION_ID]);
        $event->getRequest()->headers->add([CsrfRequestManager::CSRF_HEADER => '_stub_value']);
        $event->getRequest()->setMethod('POST');

        $innerListener = $this->createMock(OrigRememberMeListener::class);
        $csrfRequestManager = $this->createMock(CsrfRequestManager::class);

        $innerListener->expects(self::once())
            ->method('authenticate')
            ->with($event);
        $csrfRequestManager->expects(self::once())
            ->method('isRequestTokenValid')
            ->with($event->getRequest(), false)
            ->willReturn(true);

        $listener = $this->getListener($innerListener);
        $listener->setCsrfProtectedRequestHelper(new CsrfProtectedRequestHelper($csrfRequestManager));
        $listener->switchToProcessAjaxCsrfOnlyRequest();

        $listener($event);
    }

    public function testShouldNotCallInnerAuthenticateForGetNoneCsrfProtectedRequest(): void
    {
        $event = $this->createMasterRequestEvent();
        $event->getRequest()->cookies->add([self::SESSION_NAME => self::SESSION_ID]);
        $event->getRequest()->setMethod('GET');

        $innerListener = $this->createMock(OrigRememberMeListener::class);
        $csrfRequestManager = $this->createMock(CsrfRequestManager::class);

        $innerListener->expects(self::never())
            ->method('authenticate')
            ->with($event);
        $csrfRequestManager->expects(self::never())
            ->method('isRequestTokenValid')
            ->with($event->getRequest(), false);

        $listener = $this->getListener($innerListener);
        $listener->setCsrfProtectedRequestHelper(new CsrfProtectedRequestHelper($csrfRequestManager));
        $listener->switchToProcessAjaxCsrfOnlyRequest();

        $listener($event);
    }

    public function testShouldNotCallInnerAuthenticateForPostNoneCsrfProtectedRequest(): void
    {
        $event = $this->createMasterRequestEvent();
        $event->getRequest()->cookies->add([self::SESSION_NAME => self::SESSION_ID]);
        $event->getRequest()->setMethod('POST');

        $innerListener = $this->createMock(OrigRememberMeListener::class);
        $csrfRequestManager = $this->createMock(CsrfRequestManager::class);

        $innerListener->expects(self::never())
            ->method('authenticate')
            ->with($event);
        $csrfRequestManager->expects(self::once())
            ->method('isRequestTokenValid')
            ->with($event->getRequest(), false)
            ->willReturn(false);

        $listener = $this->getListener($innerListener);
        $listener->setCsrfProtectedRequestHelper(new CsrfProtectedRequestHelper($csrfRequestManager));
        $listener->switchToProcessAjaxCsrfOnlyRequest();

        $listener($event);
    }

    private function getListener(OrigRememberMeListener $innerListener): RememberMeListener
    {
        return new RememberMeListener($innerListener);
    }

    private function createMasterRequestEvent(bool $isXmlHttpRequest = false): RequestEvent
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
            HttpKernelInterface::MASTER_REQUEST
        );
    }
}
