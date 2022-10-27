<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Mailer\Transport;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ImapBundle\Mailer\Transport\DsnFromUserEmailOriginFactory;
use Oro\Bundle\ImapBundle\Mailer\Transport\UserEmailOriginTransport;
use Oro\Bundle\ImapBundle\Mailer\Transport\UserEmailOriginTransportFactory;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Transport\Dsn;

class UserEmailOriginTransportFactoryTest extends \PHPUnit\Framework\TestCase
{
    private Transport|\PHPUnit\Framework\MockObject\MockObject $transportFactory;

    private ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject $managerRegistry;

    private DsnFromUserEmailOriginFactory|\PHPUnit\Framework\MockObject\MockObject $dsnFromUserEmailOriginFactory;

    private RequestStack $requestStack;

    private UserEmailOriginTransportFactory $factory;

    protected function setUp(): void
    {
        $this->transportFactory = $this->createMock(Transport::class);
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
     *
     * @param Dsn $dsn
     * @param bool $expected
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
