<?php

namespace Oro\Component\DoctrineUtils\Tests\Unit\ORM;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\QuoteStrategy;
use Doctrine\ORM\Query\AST\Literal;
use Doctrine\ORM\Query\AST\OrderByItem;
use Doctrine\ORM\Query\AST\SelectExpression;
use Doctrine\ORM\Query\ParserResult;
use Oro\Component\DoctrineUtils\ORM\SqlWalker;

class SqlWalkerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider orderByItemDataProvider
     * @param string $type
     * @param string $platformClass
     * @param string $expected
     */
    public function testWalkOrderByItem($type, $platformClass, $expected)
    {
        $orderByItem = new OrderByItem('test');
        $orderByItem->type = $type;
        $platform = $this->createMock($platformClass);

        /** @var Connection|\PHPUnit_Framework_MockObject_MockObject $connection */
        $connection = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $connection->expects($this->any())
            ->method('getDatabasePlatform')
            ->willReturn($platform);

        /** @var QuoteStrategy|\PHPUnit_Framework_MockObject_MockObject $quoteStrategy */
        $quoteStrategy = $this->createMock(QuoteStrategy::class);
        $quoteStrategy->expects($this->any())
            ->method('getColumnAlias')
            ->willReturn('_test');

        /** @var Configuration|\PHPUnit_Framework_MockObject_MockObject $configuration */
        $configuration = $this->getMockBuilder(Configuration::class)
            ->disableOriginalConstructor()
            ->getMock();
        $configuration->expects($this->any())
            ->method('getQuoteStrategy')
            ->willReturn($quoteStrategy);

        /** @var EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject $em */
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->any())
            ->method('getConnection')
            ->willReturn($connection);
        $em->expects($this->any())
            ->method('getConfiguration')
            ->willReturn($configuration);

        /** @var AbstractQuery|\PHPUnit_Framework_MockObject_MockObject $query */
        $query = $this->getMockBuilder(AbstractQuery::class)
            ->disableOriginalConstructor()
            ->setMethods(['getEntityManager'])
            ->getMockForAbstractClass();
        $query->expects($this->any())
            ->method('getEntityManager')
            ->willReturn($em);
        /** @var ParserResult|\PHPUnit_Framework_MockObject_MockObject $parserResult */
        $parserResult = $this->getMockBuilder(ParserResult::class)
            ->disableOriginalConstructor()
            ->getMock();

        $walker = new SqlWalker($query, $parserResult, ['test' => ['token' => ['value' => 'test']]]);

        $pathExpression = new Literal(Literal::NUMERIC, '1 as test');
        $selectExpression = new SelectExpression($pathExpression, 'test', true);
        $walker->walkSelectExpression($selectExpression);

        $this->assertEquals($expected, $walker->walkOrderByItem($orderByItem));
    }

    /**
     * @return array
     */
    public function orderByItemDataProvider()
    {
        return [
            'mysql ASC' => ['ASC', MySqlPlatform::class, '_test ASC'],
            'mysql DESC' => ['DESC', MySqlPlatform::class, '_test DESC'],
            'pgsql ASC' => ['ASC', PostgreSqlPlatform::class, '_test ASC NULLS FIRST'],
            'pgsql DESC' => ['DESC', PostgreSqlPlatform::class, '_test DESC NULLS LAST'],
        ];
    }
}
