<?php

namespace Oro\Bundle\DistributionBundle\Tests\Unit\Translation;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\Expression\ExpressionBuilder;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Statement;
use Oro\Bundle\DistributionBundle\Translation\DbalTranslationLoader;
use Symfony\Component\Translation\MessageCatalogue;

class DbalTranslationLoaderTest extends \PHPUnit\Framework\TestCase
{
    /** @var Connection|\PHPUnit\Framework\MockObject\MockObject */
    protected $connection;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    protected $registry;

    /** @var DbalTranslationLoader */
    protected $loader;

    protected function setUp()
    {
        $this->connection = $this->createMock(Connection::class);

        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->registry->expects($this->any())->method('getConnection')->willReturn($this->connection);

        $this->loader = new DbalTranslationLoader($this->registry);
    }

    public function testLoad()
    {
        $statement = $this->createMock(Statement::class);
        $statement->expects($this->any())
            ->method('fetchAll')
            ->willReturn(
                [
                    ['key' => 'test_key', 'value' => 'test_value']
                ]
            );

        $expr = $this->createMock(ExpressionBuilder::class);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->any())->method('select')->willReturnSelf();
        $qb->expects($this->any())->method('from')->willReturnSelf();
        $qb->expects($this->any())->method('join')->willReturnSelf();
        $qb->expects($this->any())->method('where')->willReturnSelf();
        $qb->expects($this->any())->method('setParameter')->willReturnSelf();
        $qb->expects($this->any())->method('expr')->willReturn($expr);
        $qb->expects($this->any())->method('execute')->willReturn($statement);

        $this->connection->expects($this->once())->method('createQueryBuilder')->willReturn($qb);

        $this->assertEquals(
            new MessageCatalogue(
                'en',
                [
                    'messages' => [
                        'test_key' => 'test_value'
                    ]
                ]
            ),
            $this->loader->load(null, 'en', 'messages')
        );
    }
}
