<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Validator\Expression;

use Doctrine\DBAL\DBALException;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\QueryException;

use Oro\Bundle\WorkflowBundle\Validator\Expression\DQLExpressionVerifier;
use Oro\Bundle\WorkflowBundle\Validator\Expression\ExpressionVerifierInterface;

class DQLExpressionVerifierTest extends \PHPUnit_Framework_TestCase
{
    /** @var ExpressionVerifierInterface */
    protected $verifier;

    public function setUp()
    {
        $this->verifier = new DQLExpressionVerifier();
    }

    public function tearDown()
    {
        unset($this->verifier);
    }

    public function testValidSelectDQL()
    {
        $query = $this->createQuery('Doctrine\ORM\Query\AST\SelectStatement');
        $query->expects($this->once())->method('setFirstResult')->with(0)->willReturnSelf();
        $query->expects($this->once())->method('setMaxResults')->with(1)->willReturnSelf();
        $query->expects($this->once())->method('execute');

        $this->assertTrue($this->verifier->verify($query));
    }

    /**
     * @dataProvider validNonSelectDQLProvider
     *
     * @param string $class
     */
    public function testValidNonSelectDQL($class)
    {
        $query = $this->createQuery($class);
        $query->expects($this->never())->method('setFirstResult');
        $query->expects($this->never())->method('setMaxResults');
        $query->expects($this->never())->method('execute');

        $this->assertTrue($this->verifier->verify($query));
    }

    /**
     * @return array
     */
    public function validNonSelectDQLProvider()
    {
        return [
            ['Doctrine\ORM\Query\AST\DeleteStatement'],
            ['Doctrine\ORM\Query\AST\UpdateStatement']
        ];
    }

    public function testVerifyQueryException()
    {
        $exception = new QueryException('WRONG DQL');

        $query = $this->createQuery('Doctrine\ORM\Query\AST\SelectStatement');
        $query->expects($this->once())->method('setFirstResult')->with(0)->willReturnSelf();
        $query->expects($this->once())->method('setMaxResults')->with(1)->willReturnSelf();
        $query->expects($this->once())->method('execute')->willThrowException($exception);

        $this->setExpectedException(
            'Oro\Bundle\WorkflowBundle\Validator\Expression\Exception\ExpressionException',
            $exception->getMessage()
        );

        $this->verifier->verify($query);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage $expression must be instance of Doctrine\ORM\AbstractQuery. "string" given
     */
    public function testVerifyWithInvalidData()
    {
        $this->verifier->verify('string');
    }

    /**
     * @param string $statementClass
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|AbstractQuery
     */
    protected function createQuery($statementClass)
    {
        $statement = $this->getMockBuilder($statementClass)->disableOriginalConstructor()->getMock();

        $query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->setMethods(['setFirstResult', 'setMaxResults', 'execute', 'getAST'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $query->expects($this->atLeastOnce())->method('getAST')->willReturn($statement);

        return $query;
    }
}
