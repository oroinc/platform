<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\EventListener;

use Oro\Bundle\ApiBundle\EventListener\SecurityFirewallExceptionListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Authentication\AuthenticationTrustResolverInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Symfony\Component\Security\Http\HttpUtils;

class SecurityFirewallExceptionListenerTest extends \PHPUnit\Framework\TestCase
{
    private const SESSION_NAME = 'TEST_SESSION_ID';

    public function testSetSessionName()
    {
        $listener = $this->createSecurityFirewallExceptionListener();
        $listener->setSessionName(self::SESSION_NAME);

        self::assertObjectHasAttribute('sessionName', $listener);
        self::assertAttributeEquals(self::SESSION_NAME, 'sessionName', $listener);
    }

    /**
     * @param \Exception $exception
     *
     * @dataProvider getExceptionProvider
     */
    public function testSetTargetPathShouldCallParentWithCookie(\Exception $exception)
    {
        $event = $this->createEvent($exception);
        $event->getRequest()->cookies->add([self::SESSION_NAME => 'o595fqdg5214u4e4nfcs3uc923']);

        $session = $this->createMock(Session::class);
        $session->expects(self::once())
            ->method('set')
            ->with('_security.key.target_path', 'http://localhost/');
        $event->getRequest()->setSession($session);

        $listener = $this->createSecurityFirewallExceptionListener(true);
        $listener->setSessionName(self::SESSION_NAME);
        $listener->onKernelException($event);
    }

    /**
     * @param \Exception $exception
     *
     * @dataProvider getExceptionProvider
     */
    public function testSetTargetPathShouldNotCallParentWithoutCookie(\Exception $exception)
    {
        $event = $this->createEvent($exception);

        $session = $this->createMock(Session::class);
        $session->expects(self::never())->method('set');
        $event->getRequest()->setSession($session);

        $listener = $this->createSecurityFirewallExceptionListener(true);
        $listener->setSessionName(self::SESSION_NAME);
        $listener->onKernelException($event);
    }

    public function getExceptionProvider()
    {
        return [
            [new AccessDeniedException()],
            [new \LogicException('random', 0, new AccessDeniedException('embed', new AuthenticationException()))],
            [new AccessDeniedException('random', new \LogicException())]
        ];
    }

    /**
     * @param bool $fullSetup
     *
     * @return SecurityFirewallExceptionListener
     */
    protected function createSecurityFirewallExceptionListener($fullSetup = false)
    {
        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $trustResolver = $this->createMock(AuthenticationTrustResolverInterface::class);
        $authenticationEntryPoint = null;

        if ($fullSetup) {
            $tokenStorage->expects(self::once())
                ->method('getToken')
                ->willReturn($this->createMock(TokenInterface::class));
            $trustResolver->expects(self::once())
                ->method('isFullFledged')
                ->willReturn(false);
            $authenticationEntryPoint = $this->createMock(AuthenticationEntryPointInterface::class);
            $authenticationEntryPoint->expects(self::once())
                ->method('start')
                ->willReturn(new Response('OK'));
        }

        return new SecurityFirewallExceptionListener(
            $tokenStorage,
            $trustResolver,
            $this->createMock(HttpUtils::class),
            'key',
            $authenticationEntryPoint,
            null,
            null
        );
    }

    /**
     * @param \Exception $exception
     *
     * @return GetResponseForExceptionEvent
     */
    private function createEvent(\Exception $exception)
    {
        return new GetResponseForExceptionEvent(
            $this->createMock(HttpKernelInterface::class),
            Request::create('/'),
            HttpKernelInterface::MASTER_REQUEST,
            $exception
        );
    }
}
