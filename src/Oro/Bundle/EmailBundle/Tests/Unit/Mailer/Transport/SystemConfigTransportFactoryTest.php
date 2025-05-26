<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Mailer\Transport;

use Oro\Bundle\EmailBundle\Mailer\Transport\SystemConfigTransportFactory;
use Oro\Bundle\EmailBundle\Mailer\Transport\SystemConfigTransportRealDsnProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\TransportFactoryInterface;

class SystemConfigTransportFactoryTest extends TestCase
{
    private SystemConfigTransportRealDsnProvider&MockObject $systemConfigTransportRealDsnProvider;
    private Transport $transportFactory;
    private TransportFactoryInterface&MockObject $transportFactoryBase;
    private SystemConfigTransportFactory $factory;

    #[\Override]
    protected function setUp(): void
    {
        $this->transportFactoryBase = $this->createMock(TransportFactoryInterface::class);
        $this->transportFactory = new Transport([$this->transportFactoryBase]);
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
        $this->systemConfigTransportRealDsnProvider->expects(self::once())
            ->method('getRealDsn')
            ->with($dsn)
            ->willReturn($realDsn);

        $expectedTransport = $this->createMock(Transport\TransportInterface::class);
        $this->transportFactoryBase->expects(self::once())
            ->method('supports')
            ->willReturn(true);
        $this->transportFactoryBase->expects(self::once())
            ->method('create')
            ->willReturn($expectedTransport);

        self::assertEquals($expectedTransport, $this->factory->create($dsn));
    }
}
