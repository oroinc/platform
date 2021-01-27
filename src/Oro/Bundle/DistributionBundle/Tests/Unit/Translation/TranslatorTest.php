<?php

namespace Oro\Bundle\DistributionBundle\Tests\Unit\Translation;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Statement;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DistributionBundle\Translation\Translator;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Translation\Formatter\MessageFormatterInterface;
use Symfony\Component\Translation\Loader\LoaderInterface;
use Symfony\Component\Translation\MessageCatalogue;

class TranslatorTest extends \PHPUnit\Framework\TestCase
{
    /** @var Translator */
    protected $translator;

    protected function setUp(): void
    {
        $statement = $this->createMock(Statement::class);
        $statement->method('fetchAll')->willReturn([['code' => 'de']]);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('select')->willReturnSelf();
        $qb->method('from')->willReturnSelf();
        $qb->method('execute')->willReturn($statement);

        /** @var Connection|MockObject $connection */
        $connection = $this->createMock(Connection::class);
        $connection->method('createQueryBuilder')->willReturn($qb);

        /** @var ManagerRegistry|MockObject $registry */
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getConnection')->willReturn($connection);

        /** @var ContainerInterface|MockObject $container */
        $container = $this->createMock(ContainerInterface::class);
        $container->expects(static::any())->method('get')->with('doctrine')->willReturn($registry);

        /** @var MessageFormatterInterface|MockObject $selector */
        $selector = $this->createMock(MessageFormatterInterface::class);

        $this->translator = new class($container, $selector, 'en') extends Translator {
            public function xgetDomains(): array
            {
                return $this->domains;
            }
        };
    }

    public function testAddResource()
    {
        $this->translator->addResource('test', 'test_resource', 'en', 'messages');
        $this->translator->addResource('test', 'test_resource', 'en', 'jsmessages');

        static::assertEquals(['messages', 'jsmessages'], $this->translator->xgetDomains());
    }

    public function testInitialize()
    {
        $catalogue = new MessageCatalogue(
            'en',
            [
                'jsmessages' => [
                    'test_key' => 'test_value'
                ]
            ]
        );

        /** @var LoaderInterface|MockObject $loader */
        $loader = $this->createMock(LoaderInterface::class);
        $loader->expects($this->once())
            ->method('load')
            ->with('test_resource', 'en', 'jsmessages')
            ->willReturn($catalogue);

        $this->translator->addResource('test', 'test_resource', 'en', 'jsmessages');
        $this->translator->addLoader('test', $loader);

        $result = $this->translator->getCatalogue('en');

        static::assertNotSame($catalogue, $result);
        static::assertEquals($catalogue, $result);
    }
}
