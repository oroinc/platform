<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\EventListener;

use Oro\Bundle\SecurityBundle\Authorization\ClassAuthorizationChecker;
use Oro\Bundle\SecurityBundle\EventListener\ControllerListener;
use Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\TestDomainObject;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class ControllerListenerTest extends \PHPUnit\Framework\TestCase
{
    protected $className = 'Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\TestDomainObject';
    protected $methodName = 'getId';

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $classAuthorizationChecker;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $logger;

    /** @var Request */
    protected $request;

    /** @var ControllerListener */
    protected $listener;

    protected function setUp()
    {
        $this->classAuthorizationChecker = $this->createMock(ClassAuthorizationChecker::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->request = new Request();
        $this->request->attributes->add(['_route' => 'test']);

        $this->listener = new ControllerListener(
            $this->classAuthorizationChecker,
            $this->logger
        );
    }

    public function testAccessGranted()
    {
        $event = new FilterControllerEvent(
            $this->createMock(HttpKernelInterface::class),
            [new TestDomainObject(), $this->methodName],
            $this->request,
            HttpKernelInterface::MASTER_REQUEST
        );

        $this->logger->expects(self::once())
            ->method('debug')
            ->with(sprintf('Invoked controller "%s::%s". (MASTER_REQUEST)', $this->className, $this->methodName));

        $this->classAuthorizationChecker->expects(self::once())
            ->method('isClassMethodGranted')
            ->with($this->className, $this->methodName)
            ->willReturn(true);

        $this->listener->onKernelController($event);
    }

    /**
     * @expectedException \Symfony\Component\Security\Core\Exception\AccessDeniedException
     */
    public function testAccessDenied()
    {
        $event = new FilterControllerEvent(
            $this->createMock(HttpKernelInterface::class),
            [new TestDomainObject(), $this->methodName],
            $this->request,
            HttpKernelInterface::MASTER_REQUEST
        );

        $this->logger->expects(self::once())
            ->method('debug')
            ->with(sprintf('Invoked controller "%s::%s". (MASTER_REQUEST)', $this->className, $this->methodName));

        $this->classAuthorizationChecker->expects(self::once())
            ->method('isClassMethodGranted')
            ->with($this->className, $this->methodName)
            ->willReturn(false);

        $this->listener->onKernelController($event);
    }

    public function testAccessDeniedForSubRequest()
    {
        $event = new FilterControllerEvent(
            $this->createMock(HttpKernelInterface::class),
            [new TestDomainObject(), $this->methodName],
            $this->request,
            HttpKernelInterface::SUB_REQUEST
        );

        $this->logger->expects(self::once())
            ->method('debug')
            ->with(sprintf('Invoked controller "%s::%s". (SUB_REQUEST)', $this->className, $this->methodName));

        $this->classAuthorizationChecker->expects(self::once())
            ->method('isClassMethodGranted')
            ->with($this->className, $this->methodName)
            ->willReturn(false);

        $this->listener->onKernelController($event);
    }

    public function testUnsupportedControllerType()
    {
        $event = new FilterControllerEvent(
            $this->createMock(HttpKernelInterface::class),
            function () {
                // some controller
            },
            $this->request,
            HttpKernelInterface::MASTER_REQUEST
        );

        $this->logger->expects(self::never())
            ->method('debug');

        $this->classAuthorizationChecker->expects(self::never())
            ->method('isClassMethodGranted');

        $this->listener->onKernelController($event);
    }

    public function testAccessAlreadyChecked()
    {
        $this->request->attributes->set('_oro_access_checked', true);
        $event = new FilterControllerEvent(
            $this->createMock(HttpKernelInterface::class),
            [new TestDomainObject(), $this->methodName],
            $this->request,
            HttpKernelInterface::MASTER_REQUEST
        );

        $this->logger->expects(self::never())
            ->method('debug');

        $this->classAuthorizationChecker->expects(self::never())
            ->method('isClassMethodGranted');

        $this->listener->onKernelController($event);
    }
}
