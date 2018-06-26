<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Datagrid;

use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\EmailBundle\Datagrid\EmailGridHelper;
use Oro\Bundle\EmailBundle\Entity\InternalEmailOrigin;

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

    protected function setUp()
    {
        $this->doctrineHelper   = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $this->emailSyncManager = $this->getMockBuilder('Oro\Bundle\EmailBundle\Sync\EmailSynchronizationManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->activityManager  = $this->getMockBuilder('Oro\Bundle\ActivityBundle\Manager\ActivityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->userClass        = 'Test\User';

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

        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $datasource = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource')
            ->disableOriginalConstructor()
            ->getMock();
        $datasource->expects($this->any())
            ->method('getQueryBuilder')
            ->will($this->returnValue($qb));

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

        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $datasource = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource')
            ->disableOriginalConstructor()
            ->getMock();
        $datasource->expects($this->any())
            ->method('getQueryBuilder')
            ->will($this->returnValue($qb));

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
        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $datasource = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource')
            ->disableOriginalConstructor()
            ->getMock();
        $datasource->expects($this->any())
            ->method('getQueryBuilder')
            ->will($this->returnValue($qb));

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
        $user = $this->createMock('Oro\Bundle\UserBundle\Entity\User');
        $em   = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $repo = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManager')
            ->with($this->userClass)
            ->will($this->returnValue($em));
        $em->expects($this->once())
            ->method('getRepository')
            ->with($this->userClass)
            ->will($this->returnValue($repo));
        $repo->expects($this->once())
            ->method('find')
            ->with($userId)
            ->will($this->returnValue($user));
        $user->expects($this->once())
            ->method('getEmailOrigins')
            ->will($this->returnValue($emailOrigins));
    }
}
