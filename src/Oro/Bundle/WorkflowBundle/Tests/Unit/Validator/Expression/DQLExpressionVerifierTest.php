<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Validator\Expression;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Query\AST\DeleteStatement;
use Doctrine\ORM\Query\AST\SelectStatement;
use Doctrine\ORM\Query\AST\UpdateStatement;
use Doctrine\ORM\Query\QueryException;
use Oro\Bundle\WorkflowBundle\Validator\Expression\DQLExpressionVerifier;
use Oro\Bundle\WorkflowBundle\Validator\Expression\Exception\ExpressionException;
use Oro\Bundle\WorkflowBundle\Validator\Expression\ExpressionVerifierInterface;

class DQLExpressionVerifierTest extends \PHPUnit\Framework\TestCase
{
    /** @var ExpressionVerifierInterface */
    private $verifier;

    protected function setUp(): void
    {
        $this->verifier = new DQLExpressionVerifier();
    }

    public function testValidSelectDQL()
    {
        $query = $this->createQuery(SelectStatement::class);
        $query->expects($this->once())
            ->method('setFirstResult')
            ->with(0)
            ->willReturnSelf();
        $query->expects($this->once())
            ->method('setMaxResults')
            ->with(1)
            ->willReturnSelf();
        $query->expects($this->once())
            ->method('execute');

        $this->assertTrue($this->verifier->verify($query));
    }

    /**
     * @dataProvider validNonSelectDQLProvider
     */
    public function testValidNonSelectDQL(string $class)
    {
        $query = $this->createQuery($class);
        $query->expects($this->never())
            ->method('setFirstResult');
        $query->expects($this->never())
            ->method('setMaxResults');
        $query->expects($this->never())
            ->method('execute');

        $this->assertTrue($this->verifier->verify($query));
    }

    public function validNonSelectDQLProvider(): array
    {
        return [
            [DeleteStatement::class],
            [UpdateStatement::class]
        ];
    }

    public function testVerifyQueryException()
    {
        $exception = new QueryException('WRONG DQL');

        $query = $this->createQuery(SelectStatement::class);
        $query->expects($this->once())
            ->method('setFirstResult')
            ->with(0)
            ->willReturnSelf();
        $query->expects($this->once())
            ->method('setMaxResults')
            ->with(1)
            ->willReturnSelf();
        $query->expects($this->once())
            ->method('execute')
            ->willThrowException($exception);

        $this->expectException(ExpressionException::class);
        $this->expectExceptionMessage($exception->getMessage());

        $this->verifier->verify($query);
    }

    public function testVerifyWithInvalidData()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('$expression must be instance of Doctrine\ORM\AbstractQuery. "string" given');

        $this->verifier->verify('string');
    }

    /**
     * @return AbstractQuery|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createQuery(string $statementClass)
    {
        $statement = $this->createMock($statementClass);

        $query = $this->getMockBuilder(AbstractQuery::class)
            ->onlyMethods(['execute'])
            ->addMethods(['setFirstResult', 'setMaxResults', 'getAST'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $query->expects($this->atLeastOnce())
            ->method('getAST')
            ->willReturn($statement);

        return $query;
    }
}
