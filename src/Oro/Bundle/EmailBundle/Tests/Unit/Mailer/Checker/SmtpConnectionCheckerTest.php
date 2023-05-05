<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Mailer\Checker;

use Oro\Bundle\EmailBundle\Mailer\Checker\SmtpConnectionChecker;
use Symfony\Component\Mailer\Transport\Dsn;

class SmtpConnectionCheckerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider supportsDataProvider
     */
    public function testSupports(string $dsn, bool $expected): void
    {
        self::assertSame($expected, (new SmtpConnectionChecker())->supports(Dsn::fromString($dsn)));
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
