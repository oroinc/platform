<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\EventListener;

use Oro\Bundle\SecurityBundle\Authorization\ClassAuthorizationChecker;
use Oro\Bundle\SecurityBundle\EventListener\ControllerListener;
use Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\TestDomainObject;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class ControllerListenerTest extends \PHPUnit\Framework\TestCase
{
    private string $className = 'Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\TestDomainObject';
    private string $methodName = 'getId';

    private ClassAuthorizationChecker|\PHPUnit\Framework\MockObject\MockObject $classAuthorizationChecker;

    private LoggerInterface|\PHPUnit\Framework\MockObject\MockObject $logger;

    private Request $request;

    private ControllerListener $listener;

    protected function setUp(): void
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

    public function testAccessGranted(): void
    {
        $event = new ControllerEvent(
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

    public function testAccessDenied(): void
    {
        $this->expectException(\Symfony\Component\Security\Core\Exception\AccessDeniedException::class);
        $event = new ControllerEvent(
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

    public function testAccessDeniedForSubRequest(): void
    {
        $event = new ControllerEvent(
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

    public function testUnsupportedControllerType(): void
    {
        $event = new ControllerEvent(
            $this->createMock(HttpKernelInterface::class),
            static fn () => null,
            $this->request,
            HttpKernelInterface::MASTER_REQUEST
        );

        $this->logger->expects(self::never())
            ->method('debug');

        $this->classAuthorizationChecker->expects(self::never())
            ->method('isClassMethodGranted');

        $this->listener->onKernelController($event);
    }

    public function testAccessAlreadyChecked(): void
    {
        $this->request->attributes->set('_oro_access_checked', true);
        $event = new ControllerEvent(
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
