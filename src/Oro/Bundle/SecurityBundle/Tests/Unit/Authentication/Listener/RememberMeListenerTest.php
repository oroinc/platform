<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Authentication\Listener;

use Oro\Bundle\SecurityBundle\Authentication\Listener\RememberMeListener;
use Oro\Bundle\SecurityBundle\Csrf\CsrfRequestManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Http\Firewall\RememberMeListener as OrigRememberMeListener;

class RememberMeListenerTest extends \PHPUnit\Framework\TestCase
{
    private const SESSION_NAME = 'TEST_SESSION_ID';
    private const SESSION_ID   = 'o595fqdg5214u4e4nfcs3uc923';

    public function testShouldCallInnerAuthentificateForAnyRequestWithAjaxCsrfModeOff()
    {
        $event = $this->createMasterRequestEvent();

        $innerListener = $this->createMock(OrigRememberMeListener::class);
        $csrfRequestManager = $this->createMock(CsrfRequestManager::class);

        $innerListener->expects(self::once())
            ->method('authenticate')
            ->with($event);
        $csrfRequestManager->expects($this->never())
            ->method('isRequestTokenValid')
            ->with($event->getRequest(), false);

        $listener = $this->getListener($innerListener);
        $listener->setCsrfRequestManager($csrfRequestManager);

        $listener($event);
    }

    public function testShouldCallInnerAuthentificateForGetCsrfProtectedRequest()
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
        $csrfRequestManager->expects($this->never())
            ->method('isRequestTokenValid')
            ->with($event->getRequest(), false);

        $listener = $this->getListener($innerListener);
        $listener->setCsrfRequestManager($csrfRequestManager);
        $listener->switchToProcessAjaxCsrfOnlyRequest();

        $listener($event);
    }

    public function testShouldCallInnerAuthentificateForPostCsrfProtectedRequest()
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
        $csrfRequestManager->expects($this->once())
            ->method('isRequestTokenValid')
            ->with($event->getRequest(), false)
            ->willReturn(true);

        $listener = $this->getListener($innerListener);
        $listener->setCsrfRequestManager($csrfRequestManager);
        $listener->switchToProcessAjaxCsrfOnlyRequest();

        $listener($event);
    }

    public function testShouldNotCallInnerAuthentificateForGetNoneCsrfProtectedRequest()
    {
        $event = $this->createMasterRequestEvent();
        $event->getRequest()->cookies->add([self::SESSION_NAME => self::SESSION_ID]);
        $event->getRequest()->setMethod('GET');

        $innerListener = $this->createMock(OrigRememberMeListener::class);
        $csrfRequestManager = $this->createMock(CsrfRequestManager::class);

        $innerListener->expects(self::never())
            ->method('authenticate')
            ->with($event);
        $csrfRequestManager->expects($this->never())
            ->method('isRequestTokenValid')
            ->with($event->getRequest(), false);

        $listener = $this->getListener($innerListener);
        $listener->setCsrfRequestManager($csrfRequestManager);
        $listener->switchToProcessAjaxCsrfOnlyRequest();

        $listener($event);
    }

    public function testShouldNotCallInnerAuthentificateForPostNoneCsrfProtectedRequest()
    {
        $event = $this->createMasterRequestEvent();
        $event->getRequest()->cookies->add([self::SESSION_NAME => self::SESSION_ID]);
        $event->getRequest()->setMethod('POST');

        $innerListener = $this->createMock(OrigRememberMeListener::class);
        $csrfRequestManager = $this->createMock(CsrfRequestManager::class);

        $innerListener->expects(self::never())
            ->method('authenticate')
            ->with($event);
        $csrfRequestManager->expects($this->once())
            ->method('isRequestTokenValid')
            ->with($event->getRequest(), false)
            ->willReturn(false);

        $listener = $this->getListener($innerListener);
        $listener->setCsrfRequestManager($csrfRequestManager);
        $listener->switchToProcessAjaxCsrfOnlyRequest();

        $listener($event);
    }

    /**
     * @param OrigRememberMeListener $innerListener
     * @return RememberMeListener
     */
    private function getListener(OrigRememberMeListener $innerListener)
    {
        $sessionMock = $this->createMock(SessionInterface::class);
        $sessionMock->expects($this->any())
            ->method('getName')
            ->willReturn(self::SESSION_NAME);

        return new RememberMeListener($innerListener, $sessionMock);
    }

    /**
     * @param bool $isXmlHttpRequest
     *
     * @return RequestEvent
     */
    private function createMasterRequestEvent($isXmlHttpRequest = false)
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = new Request([], [], ['_route' => 'foo']);
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
