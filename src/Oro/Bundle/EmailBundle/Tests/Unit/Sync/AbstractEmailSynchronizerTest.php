<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Sync;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EmailBundle\Builder\EmailEntityBuilder;
use Oro\Bundle\EmailBundle\Sync\AbstractEmailSynchronizer;
use Oro\Bundle\EmailBundle\Sync\KnownEmailAddressCheckerFactory;
use Oro\Bundle\EmailBundle\Sync\KnownEmailAddressCheckerInterface;
use Oro\Bundle\EmailBundle\Sync\Model\SynchronizationProcessorSettings;
use Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\TestEmailOrigin;
use Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Exception\DisableOriginSyncException;
use Oro\Bundle\EmailBundle\Tests\Unit\Sync\Fixtures\TestEmailSynchronizationProcessor;
use Oro\Bundle\EmailBundle\Tests\Unit\Sync\Fixtures\TestEmailSynchronizer;
use Oro\Bundle\NotificationBundle\NotificationAlert\NotificationAlertManager;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\Stub\ReturnCallback;
use Psr\Log\LoggerInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class AbstractEmailSynchronizerTest extends \PHPUnit\Framework\TestCase
{
    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $em;

    /** @var NotificationAlertManager|\PHPUnit\Framework\MockObject\MockObject */
    private $notificationAlertManager;

    /** @var EmailEntityBuilder|\PHPUnit\Framework\MockObject\MockObject */
    private $emailEntityBuilder;

    /** @var KnownEmailAddressCheckerFactory|\PHPUnit\Framework\MockObject\MockObject */
    private $knownEmailAddressCheckerFactory;

    /** @var TestEmailSynchronizer */
    private $sync;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->em = $this->createMock(EntityManager::class);
        $this->notificationAlertManager = $this->createMock(NotificationAlertManager::class);
        $this->emailEntityBuilder = $this->createMock(EmailEntityBuilder::class);
        $this->knownEmailAddressCheckerFactory = $this->createMock(KnownEmailAddressCheckerFactory::class);
        $this->knownEmailAddressCheckerFactory->expects(self::any())
            ->method('create')
            ->willReturn($this->createMock(KnownEmailAddressCheckerInterface::class));

        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->doctrine->expects(self::any())
            ->method('getManager')
            ->with(null)
            ->willReturn($this->em);

        $this->sync = new TestEmailSynchronizer(
            $this->doctrine,
            $this->knownEmailAddressCheckerFactory,
            $this->emailEntityBuilder,
            $this->notificationAlertManager
        );
        $this->sync->setLogger($this->logger);
    }

    public function testSyncNoOrigin(): void
    {
        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        $maxConcurrentTasks = 3;
        $minExecPeriodInMin = 1;

        $sync = $this->getMockBuilder(TestEmailSynchronizer::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'resetHangedOrigins',
                'findOriginToSync',
                'createSynchronizationProcessor',
                'changeOriginSyncState',
                'getCurrentUtcDateTime'
            ])
            ->getMock();
        $sync->setLogger($this->logger);

        $sync->expects(self::once())
            ->method('getCurrentUtcDateTime')
            ->willReturn($now);
        $sync->expects(self::once())
            ->method('resetHangedOrigins');
        $sync->expects(self::once())
            ->method('findOriginToSync')
            ->with($maxConcurrentTasks, $minExecPeriodInMin)
            ->willReturn(null);
        $sync->expects(self::never())
            ->method('createSynchronizationProcessor');
        $this->notificationAlertManager->expects(self::never())
            ->method('addNotificationAlert');
        $this->notificationAlertManager->expects(self::never())
            ->method('resolveNotificationAlertsByAlertTypeForUserAndOrganization');

        $sync->sync($maxConcurrentTasks, $minExecPeriodInMin);
    }

    public function testSyncOriginWithDoctrineError(): void
    {
        $this->expectException(\Exception::class);
        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        $maxConcurrentTasks = 3;
        $minExecPeriodInMin = 1;
        $organization = new Organization();
        $organization->setId(234);
        $origin = new TestEmailOrigin(123);
        $origin->setOrganization($organization);

        $sync = $this->getMockBuilder(TestEmailSynchronizer::class)
            ->setConstructorArgs([
                $this->doctrine,
                $this->knownEmailAddressCheckerFactory,
                $this->emailEntityBuilder,
                $this->notificationAlertManager
            ])
            ->onlyMethods([
                'resetHangedOrigins',
                'findOriginToSync',
                'createSynchronizationProcessor',
                'changeOriginSyncState',
                'getCurrentUtcDateTime',
                'doSyncOrigin'
            ])
            ->getMock();
        $sync->setLogger($this->logger);

        $sync->expects(self::once())
            ->method('getCurrentUtcDateTime')
            ->willReturn($now);
        $sync->expects(self::once())
            ->method('resetHangedOrigins');
        $sync->expects(self::once())
            ->method('findOriginToSync')
            ->with($maxConcurrentTasks, $minExecPeriodInMin)
            ->willReturn($origin);
        $sync->expects(self::once())
            ->method('doSyncOrigin')
            ->with($origin, $this->isInstanceOf(SynchronizationProcessorSettings::class))
            ->willThrowException(new ORMException());
        $sync->expects($this->never())
            ->method('createSynchronizationProcessor');
        $this->notificationAlertManager->expects(self::never())
            ->method('addNotificationAlert');
        $this->notificationAlertManager->expects(self::exactly(2))
            ->method('resolveNotificationAlertsByAlertTypeForUserAndOrganization')
            ->willReturnMap([
                ['auth', null, 234, null],
                ['switch folder', null, 234, null]
            ]);

        $sync->sync($maxConcurrentTasks, $minExecPeriodInMin);
    }

    public function testDoSyncOrigin(): void
    {
        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        $origin = new TestEmailOrigin(123);

        $processor = $this->createMock(TestEmailSynchronizationProcessor::class);

        $sync = $this->getMockBuilder(TestEmailSynchronizer::class)
            ->setConstructorArgs([
                $this->doctrine,
                $this->knownEmailAddressCheckerFactory,
                $this->emailEntityBuilder,
                $this->notificationAlertManager
            ])
            ->onlyMethods([
                'findOriginToSync',
                'createSynchronizationProcessor',
                'changeOriginSyncState',
                'getCurrentUtcDateTime'
            ])
            ->getMock();
        $sync->setLogger($this->logger);

        $sync->expects(self::once())
            ->method('getCurrentUtcDateTime')
            ->willReturn($now);
        $sync->expects(self::once())
            ->method('createSynchronizationProcessor')
            ->with(self::identicalTo($origin))
            ->willReturn($processor);
        $sync->expects(self::exactly(2))
            ->method('changeOriginSyncState')
            ->withConsecutive(
                [self::identicalTo($origin), AbstractEmailSynchronizer::SYNC_CODE_IN_PROCESS],
                [self::identicalTo($origin), AbstractEmailSynchronizer::SYNC_CODE_SUCCESS, $now]
            )
            ->willReturn(true);
        $processor->expects($this->once())
            ->method('process')
            ->with(self::identicalTo($origin));

        $sync->callDoSyncOrigin($origin);
    }

    public function testDoSyncOriginForInProcessItem(): void
    {
        $origin = new TestEmailOrigin(123);

        $processor = $this->createMock(TestEmailSynchronizationProcessor::class);

        $sync = $this->getMockBuilder(TestEmailSynchronizer::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'findOriginToSync',
                'createSynchronizationProcessor',
                'changeOriginSyncState',
                'getCurrentUtcDateTime'
            ])
            ->getMock();
        $sync->setLogger($this->logger);

        $sync->expects(self::never())
            ->method('getCurrentUtcDateTime');
        $sync->expects(self::once())
            ->method('createSynchronizationProcessor')
            ->with($this->identicalTo($origin))
            ->willReturn($processor);
        $sync->expects(self::once())
            ->method('changeOriginSyncState')
            ->with($this->identicalTo($origin), AbstractEmailSynchronizer::SYNC_CODE_IN_PROCESS)
            ->willReturn(false);
        $processor->expects(self::never())
            ->method('process');

        $sync->callDoSyncOrigin($origin);
    }

    public function testDoSyncOriginProcessFailed(): void
    {
        $this->expectException(\Exception::class);
        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        $origin = new TestEmailOrigin(123);

        $processor = $this->createMock(TestEmailSynchronizationProcessor::class);

        $sync = $this->getMockBuilder(TestEmailSynchronizer::class)
            ->setConstructorArgs([
                $this->doctrine,
                $this->knownEmailAddressCheckerFactory,
                $this->emailEntityBuilder,
                $this->notificationAlertManager
            ])
            ->onlyMethods([
                'findOriginToSync',
                'createSynchronizationProcessor',
                'changeOriginSyncState',
                'getCurrentUtcDateTime'
            ])
            ->getMock();
        $sync->setLogger($this->logger);

        $sync->expects(self::once())
            ->method('getCurrentUtcDateTime')
            ->willReturn($now);
        $sync->expects(self::once())
            ->method('createSynchronizationProcessor')
            ->with(self::identicalTo($origin))
            ->willReturn($processor);
        $sync->expects($this->exactly(2))
            ->method('changeOriginSyncState')
            ->withConsecutive(
                [self::identicalTo($origin), AbstractEmailSynchronizer::SYNC_CODE_IN_PROCESS],
                [self::identicalTo($origin), AbstractEmailSynchronizer::SYNC_CODE_FAILURE]
            )
            ->willReturn(true);
        $processor->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($origin))
            ->willThrowException(new \Exception());

        $sync->callDoSyncOrigin($origin);
    }

    public function testDoSyncOriginSetFailureFailed(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        $origin = new TestEmailOrigin(123);

        $processor = $this->createMock(TestEmailSynchronizationProcessor::class);

        $sync = $this->getMockBuilder(TestEmailSynchronizer::class)
            ->setConstructorArgs([
                $this->doctrine,
                $this->knownEmailAddressCheckerFactory,
                $this->emailEntityBuilder,
                $this->notificationAlertManager
            ])
            ->onlyMethods([
                'findOriginToSync',
                'createSynchronizationProcessor',
                'changeOriginSyncState',
                'getCurrentUtcDateTime'
            ])
            ->getMock();
        $sync->setLogger($this->logger);

        $sync->expects(self::once())
            ->method('getCurrentUtcDateTime')
            ->willReturn($now);
        $sync->expects($this->once())
            ->method('createSynchronizationProcessor')
            ->with(self::identicalTo($origin))
            ->willReturn($processor);
        $sync->expects($this->exactly(2))
            ->method('changeOriginSyncState')
            ->withConsecutive(
                [self::identicalTo($origin), AbstractEmailSynchronizer::SYNC_CODE_IN_PROCESS],
                [self::identicalTo($origin), AbstractEmailSynchronizer::SYNC_CODE_FAILURE]
            )
            ->willReturnOnConsecutiveCalls(
                true,
                new ReturnCallback(function () {
                    throw new \Exception();
                })
            );
        $processor->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($origin))
            ->willThrowException(new \InvalidArgumentException());

        $sync->callDoSyncOrigin($origin);
    }

    /**
     * @dataProvider changeOriginSyncStateProvider
     */
    public function testChangeOriginSyncState(int $syncCode, bool $hasSynchronizedAt): void
    {
        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        $origin = new TestEmailOrigin(123);

        $q = $this->createMock(AbstractQuery::class);
        $qb = $this->createMock(QueryBuilder::class);

        $repo = $this->createMock(EntityRepository::class);
        $repo->expects(self::once())
            ->method('createQueryBuilder')
            ->with('o')
            ->willReturn($qb);

        $sets = [
            ['o.syncCode', ':code'],
            ['o.syncCodeUpdatedAt', ':updated']
        ];
        $parameters = [
            ['code', $syncCode],
            ['updated', $now],
            ['id', $origin->getId()]
        ];
        if ($hasSynchronizedAt) {
            $sets[] = ['o.synchronizedAt', ':synchronized'];
            $parameters[] = ['synchronized', $now];
        }
        if ($syncCode === AbstractEmailSynchronizer::SYNC_CODE_SUCCESS) {
            $sets[] = ['o.syncCount', 'o.syncCount + 1'];
        }
        $qb->expects(self::once())
            ->method('update')
            ->willReturnSelf();
        $qb->expects(self::exactly(count($sets)))
            ->method('set')
            ->withConsecutive(...$sets)
            ->willReturnSelf();
        $qb->expects(self::once())
            ->method('where')
            ->with('o.id = :id')
            ->willReturnSelf();
        $qb->expects(self::exactly(count($parameters)))
            ->method('setParameter')
            ->withConsecutive(...$parameters)
            ->willReturnSelf();
        if ($syncCode === AbstractEmailSynchronizer::SYNC_CODE_IN_PROCESS) {
            $qb->expects(self::once())
                ->method('andWhere')
                ->with('(o.syncCode IS NULL OR o.syncCode <> :code)')
                ->willReturnSelf();
        }
        $qb->expects(self::once())
            ->method('getQuery')
            ->willReturn($q);
        $q->expects(self::once())
            ->method('execute')
            ->willReturn(1);

        $this->em->expects(self::once())
            ->method('getRepository')
            ->with(TestEmailSynchronizer::EMAIL_ORIGIN_ENTITY)
            ->willReturn($repo);

        $this->sync->setCurrentUtcDateTime($now);
        $result = $this->sync->callChangeOriginSyncState($origin, $syncCode, $hasSynchronizedAt ? $now : null);
        $this->assertTrue($result);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testFindOriginToSync(): void
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

        $q = $this->createMock(AbstractQuery::class);
        $qb = $this->createMock(QueryBuilder::class);

        $repo = $this->createMock(EntityRepository::class);
        $repo->expects(self::once())
            ->method('createQueryBuilder')
            ->with('o')
            ->willReturn($qb);

        $qb->expects(self::once())
            ->method('select')
            ->with(
                'o'
                . ', CASE WHEN o.syncCode = :inProcess OR o.syncCode = :inProcessForce THEN 0 ELSE 1 END AS HIDDEN p1'
                . ', (TIMESTAMPDIFF(MINUTE, COALESCE(o.syncCodeUpdatedAt, :min), :now)'
                . ' - (CASE o.syncCode WHEN :success THEN 0 ELSE :timeShift END)) AS HIDDEN p2'
            )
            ->willReturnSelf();
        $qb->expects(self::once())
            ->method('where')
            ->with('o.isActive = :isActive AND (o.syncCodeUpdatedAt IS NULL OR o.syncCodeUpdatedAt <= :border)')
            ->willReturnSelf();
        $qb->expects(self::once())
            ->method('orderBy')
            ->with('p1, p2 DESC, o.syncCodeUpdatedAt')
            ->willReturnSelf();
        $qb->expects(self::exactly(10))
            ->method('setParameter')
            ->withConsecutive(
                ['inProcess', AbstractEmailSynchronizer::SYNC_CODE_IN_PROCESS],
                ['inProcessForce', AbstractEmailSynchronizer::SYNC_CODE_IN_PROCESS_FORCE],
                ['isSyncEnabled', true],
                ['success', AbstractEmailSynchronizer::SYNC_CODE_SUCCESS],
                ['isActive', true],
                ['now', $now],
                ['min', $min],
                ['border', $border],
                ['timeShift', $timeShift],
                ['isOwnerEnabled', $this->isTrue()]
            )
            ->willReturnSelf();
        $qb->expects(self::once())
            ->method('setMaxResults')
            ->with($maxConcurrentTasks + 1)
            ->willReturnSelf();
        $qb->expects(self::once())
            ->method('expr')
            ->willReturn($this->createMock(Expr::class));
        $qb->expects(self::once())
            ->method('leftJoin')
            ->willReturnSelf();
        $qb->expects(self::exactly(2))
            ->method('andWhere')
            ->willReturnSelf();
        $qb->expects(self::once())
            ->method('getQuery')
            ->willReturn($q);

        $origin1 = new TestEmailOrigin(1);
        $origin1->setSyncCode(AbstractEmailSynchronizer::SYNC_CODE_IN_PROCESS);
        $origin2 = new TestEmailOrigin(2);
        $origin2->setSyncCode(AbstractEmailSynchronizer::SYNC_CODE_SUCCESS);
        $origin3 = new TestEmailOrigin(3);
        $q->expects(self::once())
            ->method('getResult')
            ->willReturn(
                [$origin1, $origin2, $origin3]
            );

        $this->em->expects(self::once())
            ->method('getRepository')
            ->with(TestEmailSynchronizer::EMAIL_ORIGIN_ENTITY)
            ->willReturn($repo);

        $this->sync->setCurrentUtcDateTime($now);
        $result = $this->sync->callFindOriginToSync($maxConcurrentTasks, $minExecPeriodInMin);

        $this->assertEquals($origin2, $result);
    }

    public function testResetHangedOrigins(): void
    {
        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        $border = clone $now;
        $border->sub(new \DateInterval('P1D'));

        $q = $this->createMock(AbstractQuery::class);
        $qb = $this->createMock(QueryBuilder::class);

        $repo = $this->createMock(EntityRepository::class);
        $repo->expects(self::once())
            ->method('createQueryBuilder')
            ->with('o')
            ->willReturn($qb);

        $qb->expects(self::once())
            ->method('update')
            ->willReturnSelf();
        $qb->expects(self::once())
            ->method('set')
            ->with('o.syncCode', ':failure')
            ->willReturnSelf();
        $qb->expects(self::once())
            ->method('where')
            ->with('o.syncCode = :inProcess AND o.syncCodeUpdatedAt <= :border')
            ->willReturnSelf();
        $qb->expects(self::exactly(3))
            ->method('setParameter')
            ->withConsecutive(
                ['inProcess', AbstractEmailSynchronizer::SYNC_CODE_IN_PROCESS],
                ['failure', AbstractEmailSynchronizer::SYNC_CODE_FAILURE],
                ['border', $border]
            )
            ->willReturnSelf();
        $qb->expects(self::once())
            ->method('getQuery')
            ->willReturn($q);

        $q->expects(self::once())
            ->method('execute');

        $this->em->expects(self::once())
            ->method('getRepository')
            ->with(TestEmailSynchronizer::EMAIL_ORIGIN_ENTITY)
            ->willReturn($repo);

        $this->sync->setCurrentUtcDateTime($now);
        $this->sync->callResetHangedOrigins();
    }

    public function testScheduleSyncOriginsJobShouldThrowExceptionIfMessageQueueTopicIsNotSet(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Message queue topic is not set');

        $this->sync->scheduleSyncOriginsJob([1,2,3]);
    }

    public function testScheduleSyncOriginsJobShouldThrowExceptionIfMessageProducerIsNotSet(): void
    {
        ReflectionUtil::setPropertyValue($this->sync, 'messageQueueTopic', 'topic-name');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Message producer is not set');

        $this->sync->scheduleSyncOriginsJob([1,2,3]);
    }

    public function testScheduleSyncOriginsJobShouldSendMessageToTopicWithIds(): void
    {
        ReflectionUtil::setPropertyValue($this->sync, 'messageQueueTopic', 'topic-name');

        $producer = $this->createMock(MessageProducerInterface::class);
        $producer->expects(self::once())
            ->method('send')
            ->with('topic-name', ['ids' => [1,2,3]]);

        $this->sync->setMessageProducer($producer);

        $this->sync->scheduleSyncOriginsJob([1,2,3]);
    }

    public function changeOriginSyncStateProvider(): array
    {
        return [
            [AbstractEmailSynchronizer::SYNC_CODE_FAILURE, false],
            [AbstractEmailSynchronizer::SYNC_CODE_IN_PROCESS, false],
            [AbstractEmailSynchronizer::SYNC_CODE_SUCCESS, true],
        ];
    }

    public function testDoSyncOriginWithExceptionThatLeadToDisableOriginSync(): void
    {
        $exception = new DisableOriginSyncException('sync failed');
        $this->expectException(DisableOriginSyncException::class);
        $origin = new TestEmailOrigin(123);

        $processor = $this->createMock(TestEmailSynchronizationProcessor::class);

        $sync = $this->getMockBuilder(TestEmailSynchronizer::class)
            ->setConstructorArgs([
                $this->doctrine,
                $this->knownEmailAddressCheckerFactory,
                $this->emailEntityBuilder,
                $this->notificationAlertManager
            ])
            ->onlyMethods([
                'findOriginToSync',
                'createSynchronizationProcessor',
                'changeOriginSyncState',
                'getCurrentUtcDateTime'
            ])
            ->getMock();
        $sync->setLogger($this->logger);

        $sync->expects(self::never())
            ->method('getCurrentUtcDateTime');
        $sync->expects(self::once())
            ->method('createSynchronizationProcessor')
            ->with($this->identicalTo($origin))
            ->willThrowException($exception);

        $sync->expects(self::once())
            ->method('changeOriginSyncState')
            ->with($origin, AbstractEmailSynchronizer::SYNC_CODE_FAILURE, null, true)
            ->willReturn(true);
        $processor->expects(self::never())
            ->method('process');

        $sync->callDoSyncOrigin($origin);
    }
}
