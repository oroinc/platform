<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\EventListener;

use Doctrine\DBAL\Driver\PDO\Exception as PDODriverException;
use Doctrine\DBAL\Exception\DriverException;
use Oro\Bundle\EntityBundle\EventListener\ExceptionListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class ExceptionListenerTest extends TestCase
{
    private ExceptionListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->listener = new ExceptionListener();
    }

    public function testOnKernelExceptionWithDriverExceptionOutOfRange(): void
    {
        $exception = new \PDOException('out of range');
        $exception->errorInfo = ['22003', null, null];

        $driverException = new DriverException(PDODriverException::new($exception), null);
        $event = $this->createExceptionEvent($driverException);

        $this->listener->onKernelException($event);

        $this->assertInstanceOf(NotFoundHttpException::class, $event->getThrowable());
        $this->assertEquals('Object not found.', $event->getThrowable()->getMessage());
    }

    public function testOnKernelExceptionWithDriverExceptionOtherSqlState(): void
    {
        $exception = new \PDOException('different error');
        $exception->errorInfo = ['22004', null, null];

        $driverException = new DriverException(PDODriverException::new($exception), null);
        $event = $this->createExceptionEvent($driverException);

        $this->listener->onKernelException($event);

        $this->assertInstanceOf(DriverException::class, $event->getThrowable());
        $this->assertStringContainsString('different error', $event->getThrowable()->getMessage());
    }

    public function testOnKernelExceptionWithNonDriverException(): void
    {
        $runtimeException = new \RuntimeException('Some other exception');
        $event = $this->createExceptionEvent($runtimeException);

        $this->listener->onKernelException($event);

        $this->assertInstanceOf(\RuntimeException::class, $event->getThrowable());
        $this->assertEquals('Some other exception', $event->getThrowable()->getMessage());
    }

    public function testOnKernelExceptionWithDriverExceptionWithoutSqlState(): void
    {
        $pdoException = new \PDOException('error without sql state');

        $driverException = new DriverException(PDODriverException::new($pdoException), null);
        $event = $this->createExceptionEvent($driverException);

        $this->listener->onKernelException($event);

        $this->assertInstanceOf(DriverException::class, $event->getThrowable());
        $this->assertStringContainsString('error without sql state', $event->getThrowable()->getMessage());
    }

    private function createExceptionEvent(\Throwable $throwable): ExceptionEvent
    {
        return new ExceptionEvent(
            kernel: $this->createMock(HttpKernelInterface::class),
            request: new Request(),
            requestType: HttpKernelInterface::MAIN_REQUEST,
            e: $throwable
        );
    }
}
