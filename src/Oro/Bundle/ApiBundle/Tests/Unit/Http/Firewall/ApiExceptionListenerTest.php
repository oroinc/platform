<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Http\Firewall;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Authentication\AuthenticationTrustResolverInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

use Oro\Bundle\ApiBundle\Http\Firewall\ApiExceptionListener;

class ApiExceptionListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var array */
    protected $sessionOptions = ['name' => 'OROID'];

    public function testSetSessionOptions()
    {
        /** @var ApiExceptionListener $listener */
        $listener = $this->createApiExceptionListener();
        $listener->setSessionOptions($this->sessionOptions);

        $this->assertObjectHasAttribute('sessionOptions', $listener);
        $this->assertAttributeEquals($this->sessionOptions, 'sessionOptions', $listener);
    }

    /**
     * @param \Exception $exception
     *
     * @dataProvider getExceptionProvider
     */
    public function testSetTargetPathShouldCallParentWithCookie(\Exception $exception)
    {
        /**
         * Prepare
         */
        $event = $this->createEvent($exception);
        $event->getRequest()->cookies->add(['OROID' => 'o595fqdg5214u4e4nfcs3uc923']);

        /**
         * Set expectations
         */
        /** @var Session|\PHPUnit_Framework_MockObject_MockObject $session */
        $session = $this->getMockBuilder('Symfony\Component\HttpFoundation\Session\Session')
            ->disableOriginalConstructor()
            ->getMock();
        $session->expects($this->once())
            ->method('set')
            ->with('_security.key.target_path', 'http://localhost/');
        $event->getRequest()->setSession($session);

        /**
         * Execute
         */
        $listener = $this->createApiExceptionListener(true);
        $listener->setSessionOptions($this->sessionOptions);
        $listener->onKernelException($event);
    }

    /**
     * @param \Exception $exception
     *
     * @dataProvider getExceptionProvider
     */
    public function testSetTargetPathShouldNotCallParentWithoutCookie(\Exception $exception)
    {
        /**
         * Prepare
         */
        $event = $this->createEvent($exception);

        /**
         * Set expectations
         */
        /** @var Session|\PHPUnit_Framework_MockObject_MockObject $session */
        $session = $this->getMockBuilder('Symfony\Component\HttpFoundation\Session\Session')
            ->disableOriginalConstructor()
            ->getMock();
        $session->expects($this->never())->method('set');
        $event->getRequest()->setSession($session);

        /**
         * Execute
         */
        $listener = $this->createApiExceptionListener(true);
        $listener->setSessionOptions($this->sessionOptions);
        $listener->onKernelException($event);
    }

    public function getExceptionProvider()
    {
        return [
            [new AccessDeniedException()],
            [new \LogicException('random', 0, new AccessDeniedException('embed', new AuthenticationException()))],
            [new AccessDeniedException('random', new \LogicException())],
        ];
    }

    /**
     * @param bool $fullSetup
     *
     * @return ApiExceptionListener
     */
    protected function createApiExceptionListener($fullSetup = false)
    {
        /** @var TokenStorageInterface|\PHPUnit_Framework_MockObject_MockObject $tokenStorage */
        $tokenStorage = $this->getMock(
            'Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface'
        );
        /** @var AuthenticationTrustResolverInterface|\PHPUnit_Framework_MockObject_MockObject $trustResolver */
        $trustResolver = $this->getMock(
            'Symfony\Component\Security\Core\Authentication\AuthenticationTrustResolverInterface'
        );
        $authenticationEntryPoint = null;

        if ($fullSetup) {
            $tokenStorage
                ->expects($this->once())->method('getToken')
                ->will($this->returnValue(
                    $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface')
                ));
            $trustResolver
                ->expects($this->once())
                ->method('isFullFledged')
                ->will($this->returnValue(false));
            $authenticationEntryPoint = $this->getMock(
                'Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface'
            );
            $authenticationEntryPoint
                ->expects($this->once())
                ->method('start')
                ->will($this->returnValue(new Response('OK')));
        }

        return new ApiExceptionListener(
            $tokenStorage,
            $trustResolver,
            $this->getMock('Symfony\Component\Security\Http\HttpUtils'),
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
    protected function createEvent(\Exception $exception)
    {
        return new GetResponseForExceptionEvent(
            $this->getMock('Symfony\Component\HttpKernel\HttpKernelInterface'),
            Request::create('/'),
            HttpKernelInterface::MASTER_REQUEST,
            $exception
        );
    }
}
