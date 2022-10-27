<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Mailer\Checker;

use Oro\Bundle\EmailBundle\Mailer\Checker\ConnectionCheckers;
use Symfony\Component\Mailer\Transport\Dsn;

class ConnectionCheckersTest extends \PHPUnit\Framework\TestCase
{
    public function testSupportsWhenNoCheckers(): void
    {
        self::assertFalse((new ConnectionCheckers([]))->supports(Dsn::fromString('null://null')));
    }

    public function testCheckConnectionWhenNoCheckers(): void
    {
        self::assertFalse((new ConnectionCheckers([]))->checkConnection(Dsn::fromString('null://null'), $error));
        self::assertEquals('', $error);
    }

    public function testSupports(): void
    {
        $dsn = Dsn::fromString('null://null');
        $checker1 = $this->createMock(ConnectionCheckers::class);
        $checker1
            ->expects(self::atLeastOnce())
            ->method('supports')
            ->with($dsn)
            ->willReturn(false);
        $checker2 = $this->createMock(ConnectionCheckers::class);
        $checker2
            ->expects(self::atLeastOnce())
            ->method('supports')
            ->with($dsn)
            ->willReturn(true);
        $checker3 = $this->createMock(ConnectionCheckers::class);
        $checker3
            ->expects(self::never())
            ->method('supports');

        self::assertFalse((new ConnectionCheckers([$checker1]))->supports($dsn));
        self::assertTrue((new ConnectionCheckers([$checker2, $checker3]))->supports($dsn));
        self::assertTrue((new ConnectionCheckers([$checker1, $checker2, $checker3]))->supports($dsn));
    }

    public function testCheckConnection(): void
    {
        $dsn = Dsn::fromString('null://null');
        $checker1 = $this->createMock(ConnectionCheckers::class);
        $checker1
            ->expects(self::atLeastOnce())
            ->method('supports')
            ->with($dsn)
            ->willReturn(false);
        $checker1
            ->expects(self::never())
            ->method('checkConnection');
        $checker2 = $this->createMock(ConnectionCheckers::class);
        $checker2
            ->expects(self::atLeastOnce())
            ->method('supports')
            ->with($dsn)
            ->willReturn(true);
        $checker2
            ->expects(self::atLeastOnce())
            ->method('checkConnection')
            ->with($dsn)
            ->willReturn(true);
        $checker3 = $this->createMock(ConnectionCheckers::class);
        $checker3
            ->expects(self::atLeastOnce())
            ->method('supports')
            ->with($dsn)
            ->willReturn(false);
        $checker3
            ->expects(self::never())
            ->method('checkConnection');

        self::assertFalse((new ConnectionCheckers([$checker1]))->checkConnection($dsn));
        self::assertFalse((new ConnectionCheckers([$checker1, $checker3]))->checkConnection($dsn));
        self::assertTrue((new ConnectionCheckers([$checker2, $checker3]))->checkConnection($dsn));
        self::assertTrue((new ConnectionCheckers([$checker1, $checker2, $checker3]))->checkConnection($dsn));
    }
}
