<?php

namespace Oro\Bundle\SoapBundle\Tests\Unit\Handler;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\BatchBundle\ORM\QueryBuilder\CountQueryBuilderOptimizer;
use Oro\Bundle\SoapBundle\Controller\Api\EntityManagerAwareInterface;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestApiReadInterface;
use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager;
use Oro\Bundle\SoapBundle\Handler\Context;
use Oro\Bundle\SoapBundle\Handler\TotalHeaderHandler;
use Oro\Component\DoctrineUtils\ORM\SqlQuery;
use Oro\Component\DoctrineUtils\ORM\SqlQueryBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class TotalHeaderHandlerTest extends \PHPUnit\Framework\TestCase
{
    /** @var TotalHeaderHandler|\PHPUnit\Framework\MockObject\MockObject */
    private $handler;

    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $em;

    protected function setUp(): void
    {
        $optimizer = $this->createMock(CountQueryBuilderOptimizer::class);
        $optimizer->expects($this->any())
            ->method('getCountQueryBuilder')
            ->with($this->isInstanceOf(\Doctrine\ORM\QueryBuilder::class))
            ->willReturnArgument(0);

        $this->handler = $this->getMockBuilder(TotalHeaderHandler::class)
            ->setConstructorArgs([$optimizer])
            ->onlyMethods(['calculateCount'])
            ->getMock();

        $configuration = $this->createMock(Configuration::class);
        $configuration->expects($this->any())
            ->method('getDefaultQueryHints')
            ->willReturn([]);
        $configuration->expects($this->any())
            ->method('isSecondLevelCacheEnabled')
            ->willReturn(false);

        $this->em = $this->createMock(EntityManager::class);
        $this->em->expects($this->any())
            ->method('getConfiguration')
            ->willReturn($configuration);
    }

    private function createContext(object $controller = null, string $action = null): Context
    {
        return new Context(
            $controller ?: new \stdClass(),
            new Request(),
            new Response(),
            $action ?: 'test_action',
            []
        );
    }

    public function testSupportsWithTotalCountAndAction()
    {
        $context = $this->createContext(null, RestApiReadInterface::ACTION_LIST);
        $context->set('totalCount', 22);

        $this->assertTrue($this->handler->supports($context));
    }

    public function testDoesNotSupportWithOtherThenListActions()
    {
        $context = $this->createContext(null, RestApiReadInterface::ACTION_READ);
        $context->set('totalCount', 22);

        $this->assertFalse($this->handler->supports($context));
    }

    public function testSupportsWithValidQueryAndAction()
    {
        $context = $this->createContext(null, RestApiReadInterface::ACTION_LIST);
        $context->set('query', $this->getMockForAbstractClass(AbstractQuery::class, [], '', false));

        $this->assertTrue($this->handler->supports($context));
    }

    public function testDoesNotSupportWithOtherThenListActionsButValidQuery()
    {
        $context = $this->createContext(null, RestApiReadInterface::ACTION_READ);
        $context->set('query', $this->getMockForAbstractClass(AbstractQuery::class, [], '', false));

        $this->assertFalse($this->handler->supports($context));
    }

    public function testSupportsWithEntityManagerAwareController()
    {
        $context = $this->createContext(
            $this->createMock(EntityManagerAwareInterface::class),
            RestApiReadInterface::ACTION_LIST
        );

        $this->assertTrue($this->handler->supports($context));
    }

    public function testDoesNotSupportWithAnotherThenListActionsEvenControllerIsEntityManagerAwareController()
    {
        $context = $this->createContext(
            $this->createMock(EntityManagerAwareInterface::class),
            RestApiReadInterface::ACTION_READ
        );

        $this->assertFalse($this->handler->supports($context));
    }

    public function testHandleWithTotalCountCallback()
    {
        $testCount = 22;

        $this->handler->expects($this->never())
            ->method('calculateCount');

        $context = $this->createContext();
        $context->set(
            'totalCount',
            function () use ($testCount) {
                return $testCount;
            }
        );

        $this->handler->handle($context);

        $response = $context->getResponse();
        $this->assertSame((string) $testCount, $response->headers->get(TotalHeaderHandler::HEADER_NAME));
    }

    public function testHandleWithQueryBuilder()
    {
        $testCount = 22;

        $query = new Query($this->em);
        $qb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);
        $this->handler->expects($this->once())
            ->method('calculateCount')
            ->with($query)
            ->willReturn($testCount);

        $context = $this->createContext();
        $context->set('query', $qb);

        $this->handler->handle($context);

        $response = $context->getResponse();
        $this->assertSame((string) $testCount, $response->headers->get(TotalHeaderHandler::HEADER_NAME));
    }

    public function testHandleWithQuery()
    {
        $testCount = 22;

        $query = new Query($this->em);
        $this->handler->expects($this->once())
            ->method('calculateCount')
            ->with($this->isInstanceOf(Query::class))
            ->willReturn($testCount);

        $context = $this->createContext();
        $context->set('query', $query);

        $this->handler->handle($context);

        $response = $context->getResponse();
        $this->assertSame((string) $testCount, $response->headers->get(TotalHeaderHandler::HEADER_NAME));
    }

    public function testHandleWithSqlQueryBuilder()
    {
        $testCount = 22;

        $dbalQb = $this->createMock(QueryBuilder::class);
        $conn = $this->createMock(Connection::class);
        $configuration = $this->createMock(Configuration::class);
        $configuration->expects($this->once())
            ->method('getDefaultQueryHints')
            ->willReturn([]);
        $configuration->expects($this->once())
            ->method('isSecondLevelCacheEnabled')
            ->willReturn(false);

        $em = $this->createMock(EntityManager::class);
        $em->expects($this->any())
            ->method('getConfiguration')
            ->willReturn($configuration);

        $em->expects($this->once())
            ->method('getConnection')
            ->willReturn($conn);
        $conn->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($dbalQb);

        $qb = new SqlQueryBuilder($em, $this->createMock(ResultSetMapping::class));

        $dbalQb->expects($this->once())
            ->method('setMaxResults')
            ->with($this->identicalTo(null))
            ->willReturnSelf();
        $dbalQb->expects($this->once())
            ->method('setFirstResult')
            ->with($this->identicalTo(null))
            ->willReturnSelf();

        $this->handler->expects($this->once())
            ->method('calculateCount')
            ->with($this->isInstanceOf(SqlQuery::class))
            ->willReturn($testCount);

        $context = $this->createContext();
        $context->set('query', $qb);

        $this->handler->handle($context);

        $response = $context->getResponse();
        $this->assertSame((string) $testCount, $response->headers->get(TotalHeaderHandler::HEADER_NAME));
    }

    public function testHandleWithSqlQuery()
    {
        $testCount = 22;

        $configuration = $this->createMock(Configuration::class);
        $configuration->expects($this->any())
            ->method('getDefaultQueryHints')
            ->willReturn([]);
        $configuration->expects($this->once())
            ->method('isSecondLevelCacheEnabled')
            ->willReturn(false);

        $em = $this->createMock(EntityManager::class);
        $em->expects($this->any())
            ->method('getConfiguration')
            ->willReturn($configuration);

        $query = new SqlQuery($em);
        $dbalQb = $this->createMock(SqlQueryBuilder::class);
        $query->setSqlQueryBuilder($dbalQb);

        $dbalQb->expects($this->once())
            ->method('setMaxResults')
            ->with($this->identicalTo(null))
            ->willReturnSelf();
        $dbalQb->expects($this->once())
            ->method('setFirstResult')
            ->with($this->identicalTo(null))
            ->willReturnSelf();

        $this->handler->expects($this->once())
            ->method('calculateCount')
            ->with($this->isInstanceOf(SqlQuery::class))
            ->willReturn($testCount);

        $context = $this->createContext();
        $context->set('query', $query);

        $this->handler->handle($context);

        $response = $context->getResponse();
        $this->assertSame((string) $testCount, $response->headers->get(TotalHeaderHandler::HEADER_NAME));
    }

    public function testHandleWithJustManagerAwareController()
    {
        $testCount = 22;

        $entityClass = 'Test\Class';
        $controller = $this->createMock(EntityManagerAwareInterface::class);
        $context = $this->createContext($controller);
        $om = $this->createMock(ObjectManager::class);
        $metadata = $this->createMock(ClassMetadata::class);
        $repo = $this->createMock(EntityRepository::class);

        $metadata->expects($this->any())
            ->method('getName')
            ->willReturn($entityClass);

        $om->expects($this->once())
            ->method('getClassMetadata')
            ->with($entityClass)
            ->willReturn($metadata);
        $om->expects($this->once())
            ->method('getRepository')
            ->with($entityClass)
            ->willReturn($repo);

        $manager = new ApiEntityManager($entityClass, $om);
        $controller->expects($this->once())
            ->method('getManager')
            ->willReturn($manager);

        $qb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $repo->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($qb);

        $query = new Query($this->em);
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $this->handler->expects($this->once())
            ->method('calculateCount')
            ->with($query)
            ->willReturn($testCount);

        $this->handler->handle($context);

        $response = $context->getResponse();
        $this->assertSame((string) $testCount, $response->headers->get(TotalHeaderHandler::HEADER_NAME));
    }

    public function testHandleWithInvalidQueryValueThrowException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $context = $this->createContext();
        $context->set('query', false);

        $this->handler->handle($context);
    }

    public function testHandleWithInvalidTotalCountValueThrowException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $context = $this->createContext();
        $context->set('totalCount', 22);

        $this->handler->handle($context);
    }

    public function testHandleWithInvalidTotalCountCallbackThrowException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $context = $this->createContext();
        $context->set(
            'totalCount',
            function () {
                return false;
            }
        );

        $this->handler->handle($context);
    }
}
