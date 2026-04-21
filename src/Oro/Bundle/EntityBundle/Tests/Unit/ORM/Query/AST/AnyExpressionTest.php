<?php

declare(strict_types=1);

namespace Oro\Bundle\EntityBundle\Tests\Unit\ORM\Query\AST;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\ORM\Query\AST\InputParameter;
use Doctrine\ORM\Query\SqlWalker;
use Oro\Bundle\EntityBundle\ORM\Query\AST\AnyExpression;
use PHPUnit\Framework\TestCase;

class AnyExpressionTest extends TestCase
{
    public function testDispatchWithPostgreSQLPlatform(): void
    {
        $platform = $this->createMock(PostgreSQLPlatform::class);
        $connection = $this->createMock(Connection::class);
        $connection->expects($this->once())
            ->method('getDatabasePlatform')
            ->willReturn($platform);

        $sqlWalker = $this->createMock(SqlWalker::class);
        $sqlWalker->expects($this->once())
            ->method('getConnection')
            ->willReturn($connection);

        $inputParam = $this->createMock(InputParameter::class);
        $inputParam->expects($this->once())
            ->method('dispatch')
            ->with($sqlWalker)
            ->willReturn('?');

        $expression = new AnyExpression($inputParam);
        $result = $expression->dispatch($sqlWalker);

        $this->assertSame('ANY(?)', $result);
    }

    public function testDispatchWithNonPostgreSQLPlatformThrowsException(): void
    {
        $platform = $this->createMock(AbstractPlatform::class);
        $connection = $this->createMock(Connection::class);
        $connection->expects($this->once())
            ->method('getDatabasePlatform')
            ->willReturn($platform);

        $sqlWalker = $this->createMock(SqlWalker::class);
        $sqlWalker->expects($this->once())
            ->method('getConnection')
            ->willReturn($connection);

        $inputParam = new InputParameter(':ids');
        $expression = new AnyExpression($inputParam);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The ANY() expression can only be used with PostgreSQL database.');

        $expression->dispatch($sqlWalker);
    }
}
