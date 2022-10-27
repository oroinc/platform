<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Mailer\Transport;

use Oro\Bundle\EmailBundle\Mailer\Transport\SystemConfigTransportFactory;
use Oro\Bundle\EmailBundle\Mailer\Transport\SystemConfigTransportRealDsnProvider;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Transport\Dsn;

class SystemConfigTransportFactoryTest extends \PHPUnit\Framework\TestCase
{
    private Transport|\PHPUnit\Framework\MockObject\MockObject $transportFactory;

    private SystemConfigTransportRealDsnProvider $systemConfigTransportRealDsnProvider;

    private SystemConfigTransportFactory $factory;

    protected function setUp(): void
    {
        $this->transportFactory = $this->createMock(Transport::class);
        $this->systemConfigTransportRealDsnProvider = $this->createMock(SystemConfigTransportRealDsnProvider::class);
        $requestStack = new RequestStack();

        $this->factory = new SystemConfigTransportFactory(
            $this->transportFactory,
            $this->systemConfigTransportRealDsnProvider,
            $requestStack,
        );
    }

    /**
     * @dataProvider supportsDataProvider
     */
    public function testSupports(Dsn $dsn, bool $expected): void
    {
        self::assertSame($expected, $this->factory->supports($dsn));
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

    public function testCreate(): void
    {
        $dsn = Dsn::fromString('oro://system-config');
        $realDsn = Dsn::fromString('smtp://example.org');
        $this->systemConfigTransportRealDsnProvider
            ->expects(self::once())
            ->method('getRealDsn')
            ->with($dsn)
            ->willReturn($realDsn);

        $expectedTransport = $this->createMock(Transport\TransportInterface::class);
        $this->transportFactory
            ->expects(self::once())
            ->method('fromDsnObject')
            ->with($realDsn)
            ->willReturn($expectedTransport);

        self::assertEquals($expectedTransport, $this->factory->create($dsn));
    }
}
