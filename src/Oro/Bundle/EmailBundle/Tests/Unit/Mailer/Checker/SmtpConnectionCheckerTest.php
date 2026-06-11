<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Mailer\Checker;

use Oro\Bundle\EmailBundle\Mailer\Checker\SmtpConnectionChecker;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Transport\Dsn;

class SmtpConnectionCheckerTest extends TestCase
{
    /**
     * @dataProvider supportsDataProvider
     */
    public function testSupports(string $dsn, bool $expected): void
    {
        $checker = new SmtpConnectionChecker($this->createMock(LoggerInterface::class));
        self::assertSame($expected, $checker->supports(Dsn::fromString($dsn)));
    }

    public function supportsDataProvider(): array
    {
        return [
            ['smtp://127.0.0.1', true],
            ['smtps://127.0.0.1', true],
            ['native://default', false],
        ];
    }
}
