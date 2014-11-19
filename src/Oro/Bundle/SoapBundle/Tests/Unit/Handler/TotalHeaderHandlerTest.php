<?php

namespace Oro\Bundle\SoapBundle\Tests\Unit\Handler;

use Doctrine\ORM\Query;

use Oro\Bundle\SoapBundle\Handler\TotalHeaderHandler;
use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestApiReadInterface;
use Oro\Bundle\BatchBundle\ORM\QueryBuilder\CountQueryBuilderOptimizer;

class TotalHeaderHandlerTest extends \PHPUnit_Framework_TestCase
{
    use ContextAwareTest;

    /** @var CountQueryBuilderOptimizer|\PHPUnit_Framework_MockObject_MockObject */
    protected $optimizer;

    /** @var TotalHeaderHandler|\PHPUnit_Framework_MockObject_MockObject */
    protected $handler;

    protected function setUp()
    {
        $this->optimizer = $this->getMock('Oro\Bundle\BatchBundle\ORM\QueryBuilder\CountQueryBuilderOptimizer');
        $this->optimizer->expects($this->any())->method('getCountQueryBuilder')
            ->with($this->isInstanceOf('Doctrine\ORM\QueryBuilder'))->willReturnArgument(0);

        $this->handler = $this->getMockBuilder('Oro\Bundle\SoapBundle\Handler\TotalHeaderHandler')
            ->setConstructorArgs([$this->optimizer])
            ->setMethods(['calculateCount'])
            ->getMock();
    }

    protected function tearDown()
    {
        unset($this->handler, $this->optimizer);
    }

    public function testSupportsWithValidQueryAndAction()
    {
        $context = $this->createContext(null, null, null, RestApiReadInterface::ACTION_LIST);
        $context->set('query', $this->getMockForAbstractClass('Doctrine\ORM\AbstractQuery', [], '', false));

        $this->assertTrue($this->handler->supports($context));
    }

    public function testDoesNotSupportWithAnotherThenListActions()
    {
        $context = $this->createContext(null, null, null, RestApiReadInterface::ACTION_READ);
        $context->set('query', $this->getMockForAbstractClass('Doctrine\ORM\AbstractQuery', [], '', false));

        $this->assertFalse($this->handler->supports($context));
    }

    public function testSupportsWithEntityManagerAwareController()
    {
        $context = $this->createContext(
            $this->getMock('Oro\Bundle\SoapBundle\Controller\Api\EntityManagerAwareInterface'),
            null,
            null,
            RestApiReadInterface::ACTION_LIST
        );

        $this->assertTrue($this->handler->supports($context));
    }

    public function testDoesNotSupportWithAnotherThenListActionsEvenControllerIsEntityManagerAwareController()
    {
        $context = $this->createContext(
            $this->getMock('Oro\Bundle\SoapBundle\Controller\Api\EntityManagerAwareInterface'),
            null,
            null,
            RestApiReadInterface::ACTION_READ
        );

        $this->assertFalse($this->handler->supports($context));
    }

    public function testHandleWithQueryBuilder()
    {
        $testCount = 22;

        $query = new Query($this->getMock('Doctrine\ORM\EntityManager', [], [], '', false));
        $qb    = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()->getMock();
        $qb->expects($this->once())->method('getQuery')
            ->willReturn($query);
        $this->handler->expects($this->once())->method('calculateCount')->with($query)
            ->willReturn($testCount);

        $context = $this->createContext();
        $context->set('query', $qb);

        $this->handler->handle($context);

        $response = $context->getResponse();
        $this->assertSame($testCount, $response->headers->get(TotalHeaderHandler::HEADER_NAME));
    }

    public function testHandleWithQuery()
    {
        $testCount = 22;

        $query = new Query($this->getMock('Doctrine\ORM\EntityManager', [], [], '', false));
        $this->handler->expects($this->once())->method('calculateCount')
            ->with($this->isInstanceOf('Doctrine\ORM\Query'))
            ->willReturn($testCount);

        $context = $this->createContext();
        $context->set('query', $query);

        $this->handler->handle($context);

        $response = $context->getResponse();
        $this->assertSame($testCount, $response->headers->get(TotalHeaderHandler::HEADER_NAME));
    }

    public function testHandleWithJustManagerAwareController()
    {
        $testCount = 22;

        $entityClass = uniqid('testClassName');
        $controller  = $this->getMock('Oro\Bundle\SoapBundle\Controller\Api\EntityManagerAwareInterface');
        $context     = $this->createContext($controller);
        $om          = $this->getMock('Doctrine\Common\Persistence\ObjectManager');
        $metadata    = $this->getMock('Doctrine\Common\Persistence\Mapping\ClassMetadata');
        $repo        = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()->getMock();

        $metadata->expects($this->any())->method('getName')->willReturn($entityClass);

        $om->expects($this->once())->method('getClassMetadata')->with($entityClass)
            ->willReturn($metadata);
        $om->expects($this->once())->method('getRepository')->with($entityClass)
            ->willReturn($repo);

        $manager = new ApiEntityManager($entityClass, $om);
        $controller->expects($this->once())->method('getManager')
            ->willReturn($manager);

        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()->getMock();
        $repo->expects($this->once())->method('createQueryBuilder')
            ->willReturn($qb);

        $query = new Query($this->getMock('Doctrine\ORM\EntityManager', [], [], '', false));
        $qb->expects($this->once())->method('getQuery')
            ->willReturn($query);

        $this->handler->expects($this->once())->method('calculateCount')->with($query)
            ->willReturn($testCount);

        $this->handler->handle($context);

        $response = $context->getResponse();
        $this->assertSame($testCount, $response->headers->get(TotalHeaderHandler::HEADER_NAME));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testHandleWithInvalidQueryValueThrowException()
    {
        $context = $this->createContext();
        $context->set('query', false);

        $this->handler->handle($context);
    }
}
