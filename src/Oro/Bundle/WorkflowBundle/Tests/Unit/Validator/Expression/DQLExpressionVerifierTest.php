<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Validator\Expression;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\QueryException;

use Oro\Bundle\WorkflowBundle\Validator\Expression\DQLExpressionVerifier;
use Oro\Bundle\WorkflowBundle\Validator\Expression\ExpressionVerifierInterface;

class DQLExpressionVerifierTest extends \PHPUnit_Framework_TestCase
{
    const CLASS_NAME = 'stdClass';
    
    /** @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject */
    protected $registry;

    /** @var EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $manager;

    /** @var ExpressionVerifierInterface */
    protected $verifier;

    public function setUp()
    {
        $this->manager = $this->getMock('Doctrine\ORM\EntityManagerInterface');
        $this->manager->expects($this->any())->method('getConfiguration')->willReturn(new Configuration());

        $this->registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(self::CLASS_NAME)
            ->willReturn($this->manager);

        $this->verifier = new DQLExpressionVerifier($this->registry, self::CLASS_NAME);
    }

    public function tearDown()
    {
        unset($this->manager, $this->registry, $this->verifier);
    }

    public function testValidSelectDQL()
    {
        $expression = 'SELECT * FROM OroWorkflowBundle:WorkflowItem';

        $query = $this->createQuery('Doctrine\ORM\Query\AST\SelectStatement');
        $query->expects($this->once())->method('setFirstResult')->with(0)->willReturn($query);
        $query->expects($this->once())->method('setMaxResults')->with(1)->willReturn($query);
        $query->expects($this->once())->method('execute')->willReturn(null);
        $query->expects($this->any())->method('executeIgnoreQueryCache')->willReturn(null);

        $this->manager->expects($this->once())->method('createQuery')->with($expression)->willReturn($query);

        $this->assertTrue($this->verifier->verify($expression));
    }

    public function testValidDeleteDQL()
    {
        $expression = 'DELETE FROM OroWorkflowBundle:WorkflowItem';

        $query = $this->createQuery('Doctrine\ORM\Query\AST\DeleteStatement');
        $query->expects($this->never())->method('setFirstResult');
        $query->expects($this->never())->method('setMaxResults');
        $query->expects($this->never())->method('execute');

        $this->manager->expects($this->once())->method('createQuery')->with($expression)->willReturn($query);

        $this->assertTrue($this->verifier->verify($expression));
    }

    public function testValidUpdateDQL()
    {
        $expression = 'UPDATE OroWorkflowBundle:WorkflowItem as wi SET wi.id = wi.id';

        $query = $this->createQuery('Doctrine\ORM\Query\AST\UpdateStatement');
        $query->expects($this->never())->method('setFirstResult');
        $query->expects($this->never())->method('setMaxResults');
        $query->expects($this->never())->method('execute');

        $this->manager->expects($this->once())->method('createQuery')->with($expression)->willReturn($query);

        $this->assertTrue($this->verifier->verify($expression));
    }

    public function testNonValidDQL()
    {
        $expression = 'Non Valid DQL';
        $exception = new QueryException('WRONG DQL');

        $this->manager->expects($this->once())
            ->method('createQuery')
            ->with($expression)
            ->willThrowException($exception);

        $this->setExpectedException(
            'Oro\Bundle\WorkflowBundle\Validator\Expression\Exception\ExpressionException',
            $exception->getMessage()
        );

        $this->verifier->verify($expression);
    }

    public function testOtherException()
    {
        $expression = 'Non Valid DQL';
        $exception = new DBALException('Something wrong');

        $this->manager->expects($this->atLeastOnce())
            ->method('createQuery')
            ->with($expression)
            ->willThrowException($exception);

        $this->setExpectedException('Doctrine\DBAL\DBALException', $exception->getMessage());

        $this->verifier->verify($expression);
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
            ->setConstructorArgs([$this->manager])
            ->getMockForAbstractClass();

        $query->expects($this->atLeastOnce())->method('getAST')->willReturn($statement);

        return $query;
    }
}
