<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Datagrid;

use Doctrine\ORM\EntityManager;
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

class EmailGridHelperTest extends \PHPUnit\Framework\TestCase
{
    /** @var EmailGridHelper */
    private $helper;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $emailSyncManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $activityManager;

    /** @var string */
    private $userClass;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->emailSyncManager = $this->createMock(EmailSynchronizationManager::class);
        $this->activityManager = $this->createMock(ActivityManager::class);
        $this->userClass = 'Test\User';

        $this->helper = new EmailGridHelper(
            $this->doctrineHelper,
            $this->emailSyncManager,
            $this->activityManager,
            $this->userClass
        );
    }

    public function testIsUserEntity()
    {
        $this->assertTrue(
            $this->helper->isUserEntity($this->userClass)
        );
        $this->assertFalse(
            $this->helper->isUserEntity('Test\Entity')
        );
    }

    public function testGetEmailOrigins()
    {
        $userId     = 123;
        $emailOrigins = [new InternalEmailOrigin()];

        $this->setGetEmailOriginsExpectations($userId, $emailOrigins);

        $this->assertSame(
            $emailOrigins,
            $this->helper->getEmailOrigins($userId)
        );
        // call one more time to check the result is cached
        $this->assertSame(
            $emailOrigins,
            $this->helper->getEmailOrigins($userId)
        );
    }

    public function testHandleRefreshNoRefreshParameter()
    {
        $userId     = 123;
        $parameters = new ParameterBag();

        $this->doctrineHelper->expects($this->never())
            ->method('getEntityManager');
        $this->emailSyncManager->expects($this->never())
            ->method('syncOrigins');

        $this->helper->handleRefresh($parameters, $userId);
    }

    public function testHandleRefreshNoEmailOrigins()
    {
        $userId     = 123;
        $parameters = new ParameterBag([ParameterBag::ADDITIONAL_PARAMETERS => ['refresh' => true]]);

        $this->setGetEmailOriginsExpectations($userId, []);
        $this->emailSyncManager->expects($this->never())
            ->method('syncOrigins');

        $this->helper->handleRefresh($parameters, $userId);
    }

    public function testHandleRefresh()
    {
        $userId     = 123;
        $parameters = new ParameterBag([ParameterBag::ADDITIONAL_PARAMETERS => ['refresh' => true]]);

        $emailOrigins = [new InternalEmailOrigin()];

        $this->setGetEmailOriginsExpectations($userId, $emailOrigins);
        $this->emailSyncManager->expects($this->once())
            ->method('syncOrigins')
            ->with($this->identicalTo($emailOrigins));

        $this->helper->handleRefresh($parameters, $userId);
    }

    public function testUpdateDatasource()
    {
        $entityId = 123;
        $entityClass = 'Test\Entity';

        $qb = $this->createMock(QueryBuilder::class);
        $datasource = $this->createMock(OrmDatasource::class);
        $datasource->expects($this->any())
            ->method('getQueryBuilder')
            ->willReturn($qb);

        $this->activityManager->expects($this->any())
            ->method('addFilterByTargetEntity')
            ->with(
                $this->identicalTo($qb),
                $entityClass,
                $entityId
            );

        $this->helper->updateDatasource($datasource, $entityId, $entityClass);
    }

    public function testUpdateDatasourceNoEntityClass()
    {
        $entityId = 123;

        $qb = $this->createMock(QueryBuilder::class);
        $datasource = $this->createMock(OrmDatasource::class);
        $datasource->expects($this->any())
            ->method('getQueryBuilder')
            ->willReturn($qb);

        $this->activityManager->expects($this->any())
            ->method('addFilterByTargetEntity')
            ->with(
                $this->identicalTo($qb),
                $this->userClass,
                $entityId
            );

        $this->helper->updateDatasource($datasource, $entityId);
    }

    public function testUpdateDatasourceNoEntityId()
    {
        $qb = $this->createMock(QueryBuilder::class);
        $datasource = $this->createMock(OrmDatasource::class);
        $datasource->expects($this->any())
            ->method('getQueryBuilder')
            ->willReturn($qb);

        $this->activityManager->expects($this->any())
            ->method('addFilterByTargetEntity')
            ->with(
                $this->identicalTo($qb),
                $this->userClass,
                -1
            );

        $this->helper->updateDatasource($datasource, null);
    }

    protected function setGetEmailOriginsExpectations($userId, $emailOrigins)
    {
        $user = $this->createMock(User::class);
        $em = $this->createMock(EntityManager::class);
        $repo = $this->createMock(EntityRepository::class);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManager')
            ->with($this->userClass)
            ->willReturn($em);
        $em->expects($this->once())
            ->method('getRepository')
            ->with($this->userClass)
            ->willReturn($repo);
        $repo->expects($this->once())
            ->method('find')
            ->with($userId)
            ->willReturn($user);
        $user->expects($this->once())
            ->method('getEmailOrigins')
            ->willReturn($emailOrigins);
    }
}
