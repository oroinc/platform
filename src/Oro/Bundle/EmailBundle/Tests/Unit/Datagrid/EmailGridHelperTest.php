<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Datagrid;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\EmailBundle\Datagrid\EmailGridHelper;
use Oro\Bundle\EmailBundle\Entity\InternalEmailOrigin;
use Oro\Bundle\EmailBundle\Sync\EmailSynchronizationManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\UserBundle\Entity\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EmailGridHelperTest extends TestCase
{
    private const string USER_CLASS = 'Test\User';

    private DoctrineHelper&MockObject $doctrineHelper;
    private EmailSynchronizationManager&MockObject $emailSyncManager;
    private ActivityManager&MockObject $activityManager;
    private EmailGridHelper $helper;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->emailSyncManager = $this->createMock(EmailSynchronizationManager::class);
        $this->activityManager = $this->createMock(ActivityManager::class);

        $this->helper = new EmailGridHelper(
            $this->doctrineHelper,
            $this->emailSyncManager,
            $this->activityManager,
            self::USER_CLASS
        );
    }

    private function setGetEmailOriginsExpectations(int $userId, array $emailOrigins): void
    {
        $user = $this->createMock(User::class);
        $em = $this->createMock(EntityManagerInterface::class);
        $repo = $this->createMock(EntityRepository::class);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityManager')
            ->with(self::USER_CLASS)
            ->willReturn($em);
        $em->expects(self::once())
            ->method('getRepository')
            ->with(self::USER_CLASS)
            ->willReturn($repo);
        $repo->expects(self::once())
            ->method('find')
            ->with($userId)
            ->willReturn($user);
        $user->expects(self::once())
            ->method('getEmailOrigins')
            ->willReturn($emailOrigins);
    }

    public function testIsUserEntity(): void
    {
        self::assertTrue(
            $this->helper->isUserEntity(self::USER_CLASS)
        );
        self::assertFalse(
            $this->helper->isUserEntity('Test\Entity')
        );
    }

    public function testGetEmailOrigins(): void
    {
        $userId = 123;
        $emailOrigins = [new InternalEmailOrigin()];

        $this->setGetEmailOriginsExpectations($userId, $emailOrigins);

        self::assertSame(
            $emailOrigins,
            $this->helper->getEmailOrigins($userId)
        );
        // call one more time to check the result is cached
        self::assertSame(
            $emailOrigins,
            $this->helper->getEmailOrigins($userId)
        );
    }

    public function testHandleRefreshNoRefreshParameter(): void
    {
        $userId = 123;
        $parameters = new ParameterBag();

        $this->doctrineHelper->expects(self::never())
            ->method('getEntityManager');
        $this->emailSyncManager->expects(self::never())
            ->method('syncOrigins');

        $this->helper->handleRefresh($parameters, $userId);
    }

    public function testHandleRefreshNoEmailOrigins(): void
    {
        $userId = 123;
        $parameters = new ParameterBag([ParameterBag::ADDITIONAL_PARAMETERS => ['refresh' => true]]);

        $this->setGetEmailOriginsExpectations($userId, []);
        $this->emailSyncManager->expects(self::never())
            ->method('syncOrigins');

        $this->helper->handleRefresh($parameters, $userId);
    }

    public function testHandleRefresh(): void
    {
        $userId = 123;
        $parameters = new ParameterBag([ParameterBag::ADDITIONAL_PARAMETERS => ['refresh' => true]]);

        $emailOrigins = [new InternalEmailOrigin()];

        $this->setGetEmailOriginsExpectations($userId, $emailOrigins);
        $this->emailSyncManager->expects(self::once())
            ->method('syncOrigins')
            ->with($this->identicalTo($emailOrigins));

        $this->helper->handleRefresh($parameters, $userId);
    }

    public function testUpdateDatasource(): void
    {
        $entityId = 123;
        $entityClass = 'Test\Entity';

        $qb = $this->createMock(QueryBuilder::class);
        $datasource = $this->createMock(OrmDatasource::class);
        $datasource->expects(self::any())
            ->method('getQueryBuilder')
            ->willReturn($qb);

        $this->activityManager->expects(self::any())
            ->method('addFilterByTargetEntity')
            ->with(
                $this->identicalTo($qb),
                $entityClass,
                $entityId
            );

        $this->helper->updateDatasource($datasource, $entityId, $entityClass);
    }

    public function testUpdateDatasourceNoEntityClass(): void
    {
        $entityId = 123;

        $qb = $this->createMock(QueryBuilder::class);
        $datasource = $this->createMock(OrmDatasource::class);
        $datasource->expects(self::any())
            ->method('getQueryBuilder')
            ->willReturn($qb);

        $this->activityManager->expects(self::any())
            ->method('addFilterByTargetEntity')
            ->with($this->identicalTo($qb), self::USER_CLASS, $entityId);

        $this->helper->updateDatasource($datasource, $entityId);
    }

    public function testUpdateDatasourceNoEntityId(): void
    {
        $qb = $this->createMock(QueryBuilder::class);
        $datasource = $this->createMock(OrmDatasource::class);
        $datasource->expects(self::any())
            ->method('getQueryBuilder')
            ->willReturn($qb);

        $this->activityManager->expects(self::any())
            ->method('addFilterByTargetEntity')
            ->with($this->identicalTo($qb), self::USER_CLASS, -1);

        $this->helper->updateDatasource($datasource, null);
    }
}
