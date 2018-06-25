<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Sync;

use Doctrine\ORM\ORMException;
use Doctrine\ORM\Query\Expr;
use Oro\Bundle\EmailBundle\Sync\AbstractEmailSynchronizer;
use Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\TestEmailOrigin;
use Oro\Bundle\EmailBundle\Tests\Unit\Sync\Fixtures\TestEmailSynchronizer;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

class AbstractEmailSynchronizerTest extends \PHPUnit\Framework\TestCase
{
    /** @var TestEmailSynchronizer */
    private $sync;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $em;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $emailEntityBuilder;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $knownEmailAddressChecker;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $knownEmailAddressCheckerFactory;

    protected function setUp()
    {
        $this->logger = $this->createMock('Psr\Log\LoggerInterface');
        $this->em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->emailEntityBuilder = $this->getMockBuilder('Oro\Bundle\EmailBundle\Builder\EmailEntityBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $this->knownEmailAddressChecker =
            $this->getMockBuilder('Oro\Bundle\EmailBundle\Sync\KnownEmailAddressCheckerInterface')
                ->disableOriginalConstructor()
                ->getMock();
        $this->knownEmailAddressCheckerFactory =
            $this->getMockBuilder('Oro\Bundle\EmailBundle\Sync\KnownEmailAddressCheckerFactory')
                ->disableOriginalConstructor()
                ->getMock();
        $this->knownEmailAddressCheckerFactory->expects($this->any())
            ->method('create')
            ->will($this->returnValue($this->knownEmailAddressChecker));

        $this->doctrine = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrine->expects($this->any())
            ->method('getManager')
            ->with(null)
            ->will($this->returnValue($this->em));

        $this->sync = new TestEmailSynchronizer(
            $this->doctrine,
            $this->knownEmailAddressCheckerFactory,
            $this->emailEntityBuilder
        );
        $this->sync->setLogger($this->logger);
    }

    public function testSyncNoOrigin()
    {
        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        $maxConcurrentTasks = 3;
        $minExecPeriodInMin = 1;

        $sync = $this->getMockBuilder('Oro\Bundle\EmailBundle\Tests\Unit\Sync\Fixtures\TestEmailSynchronizer')
            ->disableOriginalConstructor()
            ->setMethods(
                array(
                    'resetHangedOrigins',
                    'findOriginToSync',
                    'createSynchronizationProcessor',
                    'changeOriginSyncState',
                    'getCurrentUtcDateTime'
                )
            )
            ->getMock();
        $sync->setLogger($this->logger);

        $sync->expects($this->once())
            ->method('getCurrentUtcDateTime')
            ->will($this->returnValue($now));
        $sync->expects($this->once())
            ->method('resetHangedOrigins');
        $sync->expects($this->once())
            ->method('findOriginToSync')
            ->with($maxConcurrentTasks, $minExecPeriodInMin)
            ->will($this->returnValue(null));
        $sync->expects($this->never())
            ->method('createSynchronizationProcessor');

        $sync->sync($maxConcurrentTasks, $minExecPeriodInMin);
    }

    /**
     * @expectedException \Exception
     */
    public function testSyncOriginWithDoctrineError()
    {
        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        $maxConcurrentTasks = 3;
        $minExecPeriodInMin = 1;
        $origin = new TestEmailOrigin(123);

        $sync = $this->getMockBuilder('Oro\Bundle\EmailBundle\Tests\Unit\Sync\Fixtures\TestEmailSynchronizer')
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'resetHangedOrigins',
                    'findOriginToSync',
                    'createSynchronizationProcessor',
                    'changeOriginSyncState',
                    'getCurrentUtcDateTime',
                    'doSyncOrigin'
                ]
            )
            ->getMock();
        $sync->setLogger($this->logger);

        $sync->expects($this->once())
            ->method('getCurrentUtcDateTime')
            ->will($this->returnValue($now));
        $sync->expects($this->once())
            ->method('resetHangedOrigins');
        $sync->expects($this->once())
            ->method('findOriginToSync')
            ->with($maxConcurrentTasks, $minExecPeriodInMin)
            ->will($this->returnValue($origin));
        $sync->expects($this->once())
            ->method('doSyncOrigin')
            ->with($origin, $this->isInstanceOf('Oro\Bundle\EmailBundle\Sync\Model\SynchronizationProcessorSettings'))
            ->will($this->throwException(new ORMException()));
        $sync->expects($this->never())
            ->method('createSynchronizationProcessor');

        $sync->sync($maxConcurrentTasks, $minExecPeriodInMin);
    }

    public function testDoSyncOrigin()
    {
        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        $origin = new TestEmailOrigin(123);

        $processor =
            $this->getMockBuilder('Oro\Bundle\EmailBundle\Tests\Unit\Sync\Fixtures\TestEmailSynchronizationProcessor')
                ->disableOriginalConstructor()
                ->getMock();

        $sync = $this->getMockBuilder('Oro\Bundle\EmailBundle\Tests\Unit\Sync\Fixtures\TestEmailSynchronizer')
            ->setConstructorArgs(
                [
                    $this->doctrine,
                    $this->knownEmailAddressCheckerFactory,
                    $this->emailEntityBuilder
                ]
            )
            ->setMethods(
                array(
                    'findOriginToSync',
                    'createSynchronizationProcessor',
                    'changeOriginSyncState',
                    'getCurrentUtcDateTime'
                )
            )
            ->getMock();
        $sync->setLogger($this->logger);

        $sync->expects($this->once())
            ->method('getCurrentUtcDateTime')
            ->will($this->returnValue($now));
        $sync->expects($this->once())
            ->method('createSynchronizationProcessor')
            ->with($this->identicalTo($origin))
            ->will($this->returnValue($processor));
        $sync->expects($this->at(1))
            ->method('changeOriginSyncState')
            ->with($this->identicalTo($origin), AbstractEmailSynchronizer::SYNC_CODE_IN_PROCESS)
            ->will($this->returnValue(true));
        $processor->expects($this->once())
            ->method('process')
            ->with($this->identicalTo($origin));
        $sync->expects($this->at(3))
            ->method('changeOriginSyncState')
            ->with(
                $this->identicalTo($origin),
                AbstractEmailSynchronizer::SYNC_CODE_SUCCESS,
                $this->equalTo($now)
            );

        $sync->callDoSyncOrigin($origin);
    }

    public function testDoSyncOriginForInProcessItem()
    {
        $origin = new TestEmailOrigin(123);

        $processor =
            $this->getMockBuilder('Oro\Bundle\EmailBundle\Tests\Unit\Sync\Fixtures\TestEmailSynchronizationProcessor')
                ->disableOriginalConstructor()
                ->getMock();

        $sync = $this->getMockBuilder('Oro\Bundle\EmailBundle\Tests\Unit\Sync\Fixtures\TestEmailSynchronizer')
            ->disableOriginalConstructor()
            ->setMethods(
                array(
                    'findOriginToSync',
                    'createSynchronizationProcessor',
                    'changeOriginSyncState',
                    'getCurrentUtcDateTime'
                )
            )
            ->getMock();
        $sync->setLogger($this->logger);

        $sync->expects($this->never())
            ->method('getCurrentUtcDateTime');
        $sync->expects($this->once())
            ->method('createSynchronizationProcessor')
            ->with($this->identicalTo($origin))
            ->will($this->returnValue($processor));
        $sync->expects($this->once())
            ->method('changeOriginSyncState')
            ->with($this->identicalTo($origin), AbstractEmailSynchronizer::SYNC_CODE_IN_PROCESS)
            ->will($this->returnValue(false));
        $processor->expects($this->never())
            ->method('process');

        $sync->callDoSyncOrigin($origin);
    }

    /**
     * @expectedException \Exception
     */
    public function testDoSyncOriginProcessFailed()
    {
        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        $origin = new TestEmailOrigin(123);

        $processor =
            $this->getMockBuilder('Oro\Bundle\EmailBundle\Tests\Unit\Sync\Fixtures\TestEmailSynchronizationProcessor')
                ->disableOriginalConstructor()
                ->getMock();

        $sync = $this->getMockBuilder('Oro\Bundle\EmailBundle\Tests\Unit\Sync\Fixtures\TestEmailSynchronizer')
            ->disableOriginalConstructor()
            ->setMethods(
                array(
                    'findOriginToSync',
                    'createSynchronizationProcessor',
                    'changeOriginSyncState',
                    'getCurrentUtcDateTime'
                )
            )
            ->getMock();
        $sync->setLogger($this->logger);

        $sync->expects($this->once())
            ->method('getCurrentUtcDateTime')
            ->will($this->returnValue($now));
        $sync->expects($this->once())
            ->method('createSynchronizationProcessor')
            ->with($this->identicalTo($origin))
            ->will($this->returnValue($processor));
        $sync->expects($this->at(1))
            ->method('changeOriginSyncState')
            ->with($this->identicalTo($origin), AbstractEmailSynchronizer::SYNC_CODE_IN_PROCESS)
            ->will($this->returnValue(true));
        $processor->expects($this->once())
            ->method('process')
            ->with($this->identicalTo($origin))
            ->will($this->throwException(new \Exception()));
        $sync->expects($this->at(3))
            ->method('changeOriginSyncState')
            ->with($this->identicalTo($origin), AbstractEmailSynchronizer::SYNC_CODE_FAILURE);

        $sync->callDoSyncOrigin($origin);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testDoSyncOriginSetFailureFailed()
    {
        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        $origin = new TestEmailOrigin(123);

        $processor =
            $this->getMockBuilder('Oro\Bundle\EmailBundle\Tests\Unit\Sync\Fixtures\TestEmailSynchronizationProcessor')
                ->disableOriginalConstructor()
                ->getMock();

        $sync = $this->getMockBuilder('Oro\Bundle\EmailBundle\Tests\Unit\Sync\Fixtures\TestEmailSynchronizer')
            ->disableOriginalConstructor()
            ->setMethods(
                array(
                    'findOriginToSync',
                    'createSynchronizationProcessor',
                    'changeOriginSyncState',
                    'getCurrentUtcDateTime'
                )
            )
            ->getMock();
        $sync->setLogger($this->logger);

        $sync->expects($this->once())
            ->method('getCurrentUtcDateTime')
            ->will($this->returnValue($now));
        $sync->expects($this->once())
            ->method('createSynchronizationProcessor')
            ->with($this->identicalTo($origin))
            ->will($this->returnValue($processor));
        $sync->expects($this->at(1))
            ->method('changeOriginSyncState')
            ->with($this->identicalTo($origin), AbstractEmailSynchronizer::SYNC_CODE_IN_PROCESS)
            ->will($this->returnValue(true));
        $processor->expects($this->once())
            ->method('process')
            ->with($this->identicalTo($origin))
            ->will($this->throwException(new \InvalidArgumentException()));
        $sync->expects($this->at(3))
            ->method('changeOriginSyncState')
            ->with($this->identicalTo($origin), AbstractEmailSynchronizer::SYNC_CODE_FAILURE)
            ->will($this->throwException(new \Exception()));

        $sync->callDoSyncOrigin($origin);
    }

    /**
     * @dataProvider changeOriginSyncStateProvider
     */
    public function testChangeOriginSyncState($syncCode, $hasSynchronizedAt)
    {
        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        $origin = new TestEmailOrigin(123);

        $q = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->disableOriginalConstructor()
            ->setMethods(array('execute'))
            ->getMockForAbstractClass();
        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $repo = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('o')
            ->will($this->returnValue($qb));

        $index = 0;
        $qb->expects($this->at($index++))
            ->method('update')
            ->will($this->returnValue($qb));
        $qb->expects($this->at($index++))
            ->method('set')
            ->with('o.syncCode', ':code')
            ->will($this->returnValue($qb));
        $qb->expects($this->at($index++))
            ->method('set')
            ->with('o.syncCodeUpdatedAt', ':updated')
            ->will($this->returnValue($qb));
        $qb->expects($this->at($index++))
            ->method('where')
            ->with('o.id = :id')
            ->will($this->returnValue($qb));
        $qb->expects($this->at($index++))
            ->method('setParameter')
            ->with('code', $syncCode)
            ->will($this->returnValue($qb));
        $qb->expects($this->at($index++))
            ->method('setParameter')
            ->with('updated', $this->equalTo($now))
            ->will($this->returnValue($qb));
        $qb->expects($this->at($index++))
            ->method('setParameter')
            ->with('id', $origin->getId())
            ->will($this->returnValue($qb));
        if ($hasSynchronizedAt) {
            $qb->expects($this->at($index++))
                ->method('set')
                ->with('o.synchronizedAt', ':synchronized')
                ->will($this->returnValue($qb));
            $qb->expects($this->at($index++))
                ->method('setParameter')
                ->with('synchronized', $now)
                ->will($this->returnValue($qb));
        }
        if ($syncCode === AbstractEmailSynchronizer::SYNC_CODE_IN_PROCESS) {
            $qb->expects($this->at($index++))
                ->method('andWhere')
                ->with('(o.syncCode IS NULL OR o.syncCode <> :code)')
                ->will($this->returnValue($qb));
        }
        if ($syncCode === AbstractEmailSynchronizer::SYNC_CODE_SUCCESS) {
            $qb->expects($this->at($index++))
                ->method('set')
                ->with('o.syncCount', 'o.syncCount + 1')
                ->will($this->returnValue($qb));
        }
        $qb->expects($this->at($index++))
            ->method('getQuery')
            ->will($this->returnValue($q));
        $q->expects($this->once())
            ->method('execute')
            ->will($this->returnValue(1));

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(TestEmailSynchronizer::EMAIL_ORIGIN_ENTITY)
            ->will($this->returnValue($repo));

        $this->sync->setCurrentUtcDateTime($now);
        $result = $this->sync->callChangeOriginSyncState($origin, $syncCode, $hasSynchronizedAt ? $now : null);
        $this->assertTrue($result);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testFindOriginToSync()
    {
        $maxConcurrentTasks = 2;
        $minExecPeriodInMin = 1;

        $timeShift = 30;
        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        $border = clone $now;
        if ($minExecPeriodInMin > 0) {
            $border->sub(new \DateInterval('PT' . $minExecPeriodInMin . 'M'));
        }
        $min = clone $now;
        $min->sub(new \DateInterval('P1Y'));

        $q = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->disableOriginalConstructor()
            ->setMethods(array('getResult'))
            ->getMockForAbstractClass();
        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $repo = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('o')
            ->will($this->returnValue($qb));

        $index = 0;
        $qb->expects($this->at($index++))
            ->method('select')
            ->with(
                'o'
                . ', CASE WHEN o.syncCode = :inProcess OR o.syncCode = :inProcessForce THEN 0 ELSE 1 END AS HIDDEN p1'
                . ', (TIMESTAMPDIFF(MINUTE, COALESCE(o.syncCodeUpdatedAt, :min), :now)'
                . ' - (CASE o.syncCode WHEN :success THEN 0 ELSE :timeShift END)) AS HIDDEN p2'
            )
            ->will($this->returnValue($qb));
        $qb->expects($this->at($index++))
            ->method('where')
            ->with('o.isActive = :isActive AND (o.syncCodeUpdatedAt IS NULL OR o.syncCodeUpdatedAt <= :border)')
            ->will($this->returnValue($qb));
        $qb->expects($this->at($index++))
            ->method('orderBy')
            ->with('p1, p2 DESC, o.syncCodeUpdatedAt')
            ->will($this->returnValue($qb));
        $qb->expects($this->at($index++))
            ->method('setParameter')
            ->with('inProcess', AbstractEmailSynchronizer::SYNC_CODE_IN_PROCESS)
            ->will($this->returnValue($qb));
        $qb->expects($this->at($index++))
            ->method('setParameter')
            ->with('inProcessForce', AbstractEmailSynchronizer::SYNC_CODE_IN_PROCESS_FORCE)
            ->will($this->returnValue($qb));
        $qb->expects($this->at($index++))
            ->method('setParameter')
            ->with('success', AbstractEmailSynchronizer::SYNC_CODE_SUCCESS)
            ->will($this->returnValue($qb));
        $qb->expects($this->at($index++))
            ->method('setParameter')
            ->with('isActive', true)
            ->will($this->returnValue($qb));
        $qb->expects($this->at($index++))
            ->method('setParameter')
            ->with('now', $this->equalTo($now))
            ->will($this->returnValue($qb));
        $qb->expects($this->at($index++))
            ->method('setParameter')
            ->with('min', $this->equalTo($min))
            ->will($this->returnValue($qb));
        $qb->expects($this->at($index++))
            ->method('setParameter')
            ->with('border', $this->equalTo($border))
            ->will($this->returnValue($qb));
        $qb->expects($this->at($index++))
            ->method('setParameter')
            ->with('timeShift', $this->equalTo($timeShift))
            ->will($this->returnValue($qb));
        $qb->expects($this->at($index++))
            ->method('setMaxResults')
            ->with($maxConcurrentTasks + 1)
            ->will($this->returnValue($qb));

        $expr = $this->createMock(Expr::class);
        $qb->expects($this->at($index++))
            ->method('expr')->willReturn($expr);
        $qb->expects($this->at($index++))
            ->method('leftJoin')->willReturn($qb);
        $qb->expects($this->at($index++))
            ->method('andWhere')->willReturn($qb);
        $qb->expects($this->at($index++))
            ->method('setParameter')
            ->with('isOwnerEnabled', $this->equalTo(true))
            ->willReturn($qb);

        $qb->expects($this->at($index++))
            ->method('getQuery')
            ->will($this->returnValue($q));

        $origin1 = new TestEmailOrigin(1);
        $origin1->setSyncCode(AbstractEmailSynchronizer::SYNC_CODE_IN_PROCESS);
        $origin2 = new TestEmailOrigin(2);
        $origin2->setSyncCode(AbstractEmailSynchronizer::SYNC_CODE_SUCCESS);
        $origin3 = new TestEmailOrigin(3);
        $q->expects($this->once())
            ->method('getResult')
            ->will(
                $this->returnValue(
                    array($origin1, $origin2, $origin3)
                )
            );

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(TestEmailSynchronizer::EMAIL_ORIGIN_ENTITY)
            ->will($this->returnValue($repo));

        $this->sync->setCurrentUtcDateTime($now);
        $result = $this->sync->callFindOriginToSync($maxConcurrentTasks, $minExecPeriodInMin);

        $this->assertEquals($origin2, $result);
    }

    public function testResetHangedOrigins()
    {
        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        $border = clone $now;
        $border->sub(new \DateInterval('P1D'));

        $q = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->disableOriginalConstructor()
            ->setMethods(array('execute'))
            ->getMockForAbstractClass();
        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $repo = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repo->expects($this->once())
            ->method('createQueryBuilder')
            ->with('o')
            ->will($this->returnValue($qb));

        $index = 0;
        $qb->expects($this->at($index++))
            ->method('update')
            ->will($this->returnValue($qb));
        $qb->expects($this->at($index++))
            ->method('set')
            ->with('o.syncCode', ':failure')
            ->will($this->returnValue($qb));
        $qb->expects($this->at($index++))
            ->method('where')
            ->with('o.syncCode = :inProcess AND o.syncCodeUpdatedAt <= :border')
            ->will($this->returnValue($qb));
        $qb->expects($this->at($index++))
            ->method('setParameter')
            ->with('inProcess', AbstractEmailSynchronizer::SYNC_CODE_IN_PROCESS)
            ->will($this->returnValue($qb));
        $qb->expects($this->at($index++))
            ->method('setParameter')
            ->with('failure', AbstractEmailSynchronizer::SYNC_CODE_FAILURE)
            ->will($this->returnValue($qb));
        $qb->expects($this->at($index++))
            ->method('setParameter')
            ->with('border', $this->equalTo($border))
            ->will($this->returnValue($qb));
        $qb->expects($this->at($index++))
            ->method('getQuery')
            ->will($this->returnValue($q));

        $q->expects($this->once())
            ->method('execute');

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(TestEmailSynchronizer::EMAIL_ORIGIN_ENTITY)
            ->will($this->returnValue($repo));

        $this->sync->setCurrentUtcDateTime($now);
        $this->sync->callResetHangedOrigins();
    }

    public function testScheduleSyncOriginsJobShouldThrowExceptionIfMessageQueueTopicIsNotSet()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Message queue topic is not set');

        $this->sync->scheduleSyncOriginsJob([1,2,3]);
    }

    public function testScheduleSyncOriginsJobShouldThrowExceptionIfMessageProducerIsNotSet()
    {
        $refProp = new \ReflectionProperty(TestEmailSynchronizer::class, 'messageQueueTopic');
        $refProp->setAccessible(true);
        $refProp->setValue($this->sync, 'topic-name');
        $refProp->setAccessible(false);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Message producer is not set');

        $this->sync->scheduleSyncOriginsJob([1,2,3]);
    }

    public function testScheduleSyncOriginsJobShouldSendMessageToTopicWithIds()
    {
        $refProp = new \ReflectionProperty(TestEmailSynchronizer::class, 'messageQueueTopic');
        $refProp->setAccessible(true);
        $refProp->setValue($this->sync, 'topic-name');
        $refProp->setAccessible(false);

        $producer = $this->createMessageProducerMock();
        $producer
            ->expects($this->once())
            ->method('send')
            ->with('topic-name', ['ids' => [1,2,3]])
        ;

        $this->sync->setMessageProducer($producer);

        $this->sync->scheduleSyncOriginsJob([1,2,3]);
    }

    public function changeOriginSyncStateProvider()
    {
        return array(
            array(AbstractEmailSynchronizer::SYNC_CODE_FAILURE, false),
            array(AbstractEmailSynchronizer::SYNC_CODE_IN_PROCESS, false),
            array(AbstractEmailSynchronizer::SYNC_CODE_SUCCESS, true),
        );
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|MessageProducerInterface
     */
    private function createMessageProducerMock()
    {
        return $this->createMock(MessageProducerInterface::class);
    }
}
