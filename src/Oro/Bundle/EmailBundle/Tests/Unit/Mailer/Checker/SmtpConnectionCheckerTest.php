<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Mailer\Checker;

use Oro\Bundle\EmailBundle\Mailer\Checker\SmtpConnectionChecker;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Transport\Dsn;

class SmtpConnectionCheckerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider supportsDataProvider
     *
     * @param string $dsn
     * @param bool $expected
     */
    public function testSupports(string $dsn, bool $expected): void
    {
        $checker = new SmtpConnectionChecker();
        $checker->setLogger($this->createMock(LoggerInterface::class));
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
