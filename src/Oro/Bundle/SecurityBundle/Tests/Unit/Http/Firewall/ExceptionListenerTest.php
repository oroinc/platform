<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Http\Firewall;

use Oro\Bundle\SecurityBundle\Http\Firewall\ExceptionListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Authentication\AuthenticationTrustResolverInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Symfony\Component\Security\Http\HttpUtils;

class ExceptionListenerTest extends \PHPUnit\Framework\TestCase
{
    private const EXCLUDED_ROUTE_1 = 'excluded_route_1';
    private const EXCLUDED_ROUTE_2 = 'excluded_route_2';

    /**
     * @dataProvider getExceptionProvider
     */
    public function testSetTarget(\Exception $exception): void
    {
        $event = $this->createEvent($exception);

        $session = $this->createMock(SessionInterface::class);
        $session->expects(self::once())
            ->method('set')
            ->with('_security.key.target_path', 'http://localhost/');
        $event->getRequest()->setSession($session);

        $listener = $this->createExceptionListener();
        $listener->onKernelException($event);
    }

    /**
     * @dataProvider getExceptionProvider
     */
    public function testSetTargetUnsafeMethod(\Exception $exception): void
    {
        $event = $this->createEvent($exception);
        $event->getRequest()->setMethod('POST');

        $session = $this->createMock(SessionInterface::class);
        $session->expects(self::never())
            ->method('set');
        $event->getRequest()->setSession($session);

        $listener = $this->createExceptionListener();
        $listener->onKernelException($event);
    }

    /**
     * @dataProvider getExceptionProvider
     */
    public function testSetTargetXmlHttpRequest(\Exception $exception): void
    {
        $event = $this->createEvent($exception);
        $event->getRequest()->headers->set('X-Requested-With', 'XMLHttpRequest');

        $session = $this->createMock(SessionInterface::class);
        $session->expects(self::never())
            ->method('set');
        $event->getRequest()->setSession($session);

        $listener = $this->createExceptionListener();
        $listener->onKernelException($event);
    }

    /**
     * @dataProvider shouldSetTargetPathForNonExcludedRoutesProvider
     */
    public function testShouldSetTargetPathForNonExcludedRoutes(?string $route): void
    {
        $exception = new AccessDeniedException();
        $event = $this->createEvent($exception);
        $request = $event->getRequest();
        $request->attributes->set('_route', $route);
        $session = $this->createMock(SessionInterface::class);
        $session->expects(self::once())
            ->method('set')
            ->with('_security.key.target_path', 'http://localhost/');

        $request->setSession($session);

        $listener = $this->createExceptionListener();
        $listener->onKernelException($event);
    }

    public function shouldSetTargetPathForNonExcludedRoutesProvider(): array
    {
        return [
            'no route' => [null, true],
            'not excluded route' => ['not_excluded_route', false],
        ];
    }

    /**
     * @dataProvider shouldNotSetTargetPathForExcludedRoutesProvider
     */
    public function testShouldNotSetTargetPathForExcludedRoutes(?string $route): void
    {
        $exception = new AccessDeniedException();
        $event = $this->createEvent($exception);
        $request = $event->getRequest();
        $request->attributes->set('_route', $route);
        $session = $this->createMock(SessionInterface::class);
        $session->expects(self::never())
            ->method('set');
        $request->setSession($session);

        $listener = $this->createExceptionListener();
        $listener->onKernelException($event);
    }

    public function shouldNotSetTargetPathForExcludedRoutesProvider(): array
    {
        return [
            'excluded route 1' => [self::EXCLUDED_ROUTE_1, true],
            'excluded route 2' => [self::EXCLUDED_ROUTE_2, true],
        ];
    }

    public function getExceptionProvider(): array
    {
        return [
            [new AccessDeniedException()],
            [new \LogicException('random', 0, new AccessDeniedException('embed', new AuthenticationException()))],
            [new AccessDeniedException('random', new \LogicException())],
        ];
    }

    protected function createExceptionListener(): ExceptionListener
    {
        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $trustResolver = $this->createMock(AuthenticationTrustResolverInterface::class);
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
        $listener = new ExceptionListener(
            $tokenStorage,
            $trustResolver,
            $this->createMock(HttpUtils::class),
            'key',
            $authenticationEntryPoint,
            null,
            null
        );
        $listener->setExcludedRoutes(
            [
                self::EXCLUDED_ROUTE_1,
                self::EXCLUDED_ROUTE_2,
            ]
        );

        return $listener;
    }

    private function createEvent(\Exception $exception): ExceptionEvent
    {
        return new ExceptionEvent(
            $this->createMock(HttpKernelInterface::class),
            Request::create('/'),
            HttpKernelInterface::MASTER_REQUEST,
            $exception
        );
    }
}
