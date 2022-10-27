<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\EventListener;

use Oro\Bundle\SecurityBundle\Authorization\ClassAuthorizationChecker;
use Oro\Bundle\SecurityBundle\EventListener\ControllerListener;
use Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\TestDomainObject;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class ControllerListenerTest extends \PHPUnit\Framework\TestCase
{
    private const CLASS_NAME = TestDomainObject::class;
    private const METHOD_NAME = 'getId';

    /** @var ClassAuthorizationChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $classAuthorizationChecker;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var Request */
    private $request;

    /** @var ControllerListener */
    private $listener;

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
            [new TestDomainObject(), self::METHOD_NAME],
            $this->request,
            HttpKernelInterface::MASTER_REQUEST
        );

        $this->logger->expects(self::once())
            ->method('debug')
            ->with(sprintf('Invoked controller "%s::%s". (MASTER_REQUEST)', self::CLASS_NAME, self::METHOD_NAME));

        $this->classAuthorizationChecker->expects(self::once())
            ->method('isClassMethodGranted')
            ->with(self::CLASS_NAME, self::METHOD_NAME)
            ->willReturn(true);

        $this->listener->onKernelController($event);
    }

    public function testAccessDenied(): void
    {
        $this->expectException(AccessDeniedException::class);
        $event = new ControllerEvent(
            $this->createMock(HttpKernelInterface::class),
            [new TestDomainObject(), self::METHOD_NAME],
            $this->request,
            HttpKernelInterface::MASTER_REQUEST
        );

        $this->logger->expects(self::once())
            ->method('debug')
            ->with(sprintf('Invoked controller "%s::%s". (MASTER_REQUEST)', self::CLASS_NAME, self::METHOD_NAME));

        $this->classAuthorizationChecker->expects(self::once())
            ->method('isClassMethodGranted')
            ->with(self::CLASS_NAME, self::METHOD_NAME)
            ->willReturn(false);

        $this->listener->onKernelController($event);
    }

    public function testAccessDeniedForSubRequest(): void
    {
        $event = new ControllerEvent(
            $this->createMock(HttpKernelInterface::class),
            [new TestDomainObject(), self::METHOD_NAME],
            $this->request,
            HttpKernelInterface::SUB_REQUEST
        );

        $this->logger->expects(self::once())
            ->method('debug')
            ->with(sprintf('Invoked controller "%s::%s". (SUB_REQUEST)', self::CLASS_NAME, self::METHOD_NAME));

        $this->classAuthorizationChecker->expects(self::once())
            ->method('isClassMethodGranted')
            ->with(self::CLASS_NAME, self::METHOD_NAME)
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
            [new TestDomainObject(), self::METHOD_NAME],
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
