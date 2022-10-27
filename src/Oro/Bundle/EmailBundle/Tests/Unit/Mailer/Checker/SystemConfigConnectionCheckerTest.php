<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Mailer\Checker;

use Oro\Bundle\EmailBundle\Mailer\Checker\ConnectionCheckerInterface;
use Oro\Bundle\EmailBundle\Mailer\Checker\SystemConfigConnectionChecker;
use Oro\Bundle\EmailBundle\Mailer\Transport\SystemConfigTransportRealDsnProvider;
use Symfony\Component\Mailer\Transport\Dsn;

class SystemConfigConnectionCheckerTest extends \PHPUnit\Framework\TestCase
{
    private ConnectionCheckerInterface|\PHPUnit\Framework\MockObject\MockObject $connectionCheckers;

    private SystemConfigTransportRealDsnProvider|\PHPUnit\Framework\MockObject\MockObject
        $systemConfigTransportRealDsnProvider;

    private SystemConfigConnectionChecker $checker;

    protected function setUp(): void
    {
        $this->connectionCheckers = $this->createMock(ConnectionCheckerInterface::class);
        $this->systemConfigTransportRealDsnProvider = $this->createMock(SystemConfigTransportRealDsnProvider::class);

        $this->checker = new SystemConfigConnectionChecker(
            $this->connectionCheckers,
            $this->systemConfigTransportRealDsnProvider
        );
    }

    /**
     * @dataProvider supportsDataProvider
     */
    public function testSupports(Dsn $dsn, bool $expected): void
    {
        self::assertSame($expected, $this->checker->supports($dsn));
    }

    public function supportsDataProvider(): array
    {
        return [
            ['dsn' => new Dsn('null', 'null'), 'expected' => false],
            ['dsn' => new Dsn('oro', 'null'), 'expected' => false],
            ['dsn' => new Dsn('null', 'system-config'), 'expected' => false],
            ['dsn' => new Dsn('oro', 'system-config'), 'expected' => true],
        ];
    }

    public function testCheckConnection(): void
    {
        $dsn = Dsn::fromString('oro://system-config');
        $realDsn = Dsn::fromString('smtp://example.org');

        $this->systemConfigTransportRealDsnProvider
            ->expects(self::once())
            ->method('getRealDsn')
            ->with($dsn)
            ->willReturn($realDsn);

        $this->connectionCheckers
            ->expects(self::once())
            ->method('checkConnection')
            ->with($realDsn)
            ->willReturn(true);

        self::assertTrue($this->checker->checkConnection($dsn));
    }
}
