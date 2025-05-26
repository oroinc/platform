<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Mailer\Transport;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ImapBundle\Mailer\Transport\DsnFromUserEmailOriginFactory;
use Oro\Bundle\ImapBundle\Mailer\Transport\UserEmailOriginTransport;
use Oro\Bundle\ImapBundle\Mailer\Transport\UserEmailOriginTransportFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Transport\Dsn;

class UserEmailOriginTransportFactoryTest extends TestCase
{
    private Transport $transportFactory;
    private ManagerRegistry&MockObject $managerRegistry;
    private DsnFromUserEmailOriginFactory&MockObject $dsnFromUserEmailOriginFactory;
    private RequestStack $requestStack;
    private UserEmailOriginTransportFactory $factory;

    #[\Override]
    protected function setUp(): void
    {
        $this->transportFactory = new Transport([]);
        $this->managerRegistry = $this->createMock(ManagerRegistry::class);
        $this->dsnFromUserEmailOriginFactory = $this->createMock(DsnFromUserEmailOriginFactory::class);
        $this->requestStack = new RequestStack();

        $this->factory = new UserEmailOriginTransportFactory(
            $this->transportFactory,
            $this->managerRegistry,
            $this->dsnFromUserEmailOriginFactory,
            $this->requestStack
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
            ['dsn' => new Dsn('null', 'user-email-origin'), 'expected' => false],
            ['dsn' => new Dsn('oro', 'user-email-origin'), 'expected' => true],
        ];
    }

    public function testCreate(): void
    {
        self::assertEquals(
            new UserEmailOriginTransport(
                $this->transportFactory,
                $this->managerRegistry,
                $this->dsnFromUserEmailOriginFactory,
                $this->requestStack
            ),
            $this->factory->create(new Dsn('null', 'null'))
        );
    }
}
