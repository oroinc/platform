<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\EventListener;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\DBAL\Driver\Connection;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\SecurityBundle\EventListener\OwnerTreeListener;
use Oro\Bundle\SecurityBundle\Owner\OwnerTreeProviderInterface;
use Oro\Bundle\SecurityBundle\Tests\Unit\Owner\Fixtures\Entity\TestBusinessUnit;
use Oro\Bundle\SecurityBundle\Tests\Unit\Owner\Fixtures\Entity\TestUser;
use Oro\Component\TestUtils\ORM\Mocks\EntityManagerMock;
use Oro\Component\TestUtils\ORM\Mocks\StatementMock;
use Oro\Component\TestUtils\ORM\OrmTestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class OwnerTreeListenerTest extends OrmTestCase
{
    private const ENTITY_NAMESPACE = 'Oro\Bundle\SecurityBundle\Tests\Unit\Owner\Fixtures\Entity';

    /** @var EntityManagerMock */
    private $em;

    /** @var Connection|\PHPUnit\Framework\MockObject\MockObject */
    private $connection;

    /** @var OwnerTreeProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $ownerTreeProvider;

    /** @var OwnerTreeListener */
    private $listener;

    protected function setUp(): void
    {
        $this->em = $this->getTestEntityManager();
        $this->em->getConfiguration()->setMetadataDriverImpl(new AnnotationDriver(
            new AnnotationReader(),
            self::ENTITY_NAMESPACE
        ));
        $this->em->getConfiguration()->setEntityNamespaces(['Test' => self::ENTITY_NAMESPACE]);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($this->em);

        $this->connection = $this->getDriverConnectionMock($this->em);

        $this->ownerTreeProvider = $this->createMock(OwnerTreeProviderInterface::class);

        $this->listener = new OwnerTreeListener($this->ownerTreeProvider);
        $this->listener->addSupportedClass(self::ENTITY_NAMESPACE . '\TestOrganization');
        $this->em->getEventManager()->addEventListener('onFlush', $this->listener);
        $this->em->getEventManager()->addEventListener('postFlush', $this->listener);
    }

    private function setInsertQueryExpectation()
    {
        $this->connection->expects($this->once())
            ->method('prepare')
            ->willReturn(new StatementMock());
    }

    private function findUser(int $userId, string $userName, ?int $ownerId): TestUser
    {
        $this->setQueryExpectation(
            $this->connection,
            'SELECT t0.id AS id_1, t0.username AS username_2, t0.owner_id AS owner_id_3'
            . ' FROM tbl_user t0 WHERE t0.id = ?',
            [['id_1' => $userId, 'username_2' => $userName, 'owner_id_3' => $ownerId]],
            [1 => $userId],
            [1 => \PDO::PARAM_INT]
        );

        return $this->em->getRepository(self::ENTITY_NAMESPACE . '\TestUser')->find($userId);
    }

    private function addFindUserExpectation(int $userId, string $userName, ?int $ownerId): void
    {
        $this->addQueryExpectation(
            'SELECT t0.id AS id_1, t0.username AS username_2, t0.owner_id AS owner_id_3'
            . ' FROM tbl_user t0 WHERE t0.id = ?',
            [['id_1' => $userId, 'username_2' => $userName, 'owner_id_3' => $ownerId]],
            [1 => $userId],
            [1 => \PDO::PARAM_INT]
        );
    }

    private function addLoadUserBusinessUnitsExpectation(int $userId, ?int $businessUnitId): void
    {
        $rows = [];
        if (null !== $businessUnitId) {
            $rows[] = ['id_1' => $businessUnitId, 'parent_id_2' => null, 'organization_id_3' => null];
        }
        $this->addQueryExpectation(
            'SELECT t0.id AS id_1, t0.parent_id AS parent_id_2, t0.organization_id AS organization_id_3'
            . ' FROM tbl_business_unit t0'
            . ' INNER JOIN tbl_user_to_business_unit ON t0.id = tbl_user_to_business_unit.business_unit_id'
            . ' WHERE tbl_user_to_business_unit.user_id = ?',
            $rows,
            [1 => $userId],
            [1 => \PDO::PARAM_INT]
        );
    }

    private function getBusinessUnitReference(int $businessUnitId): TestBusinessUnit
    {
        return $this->em->getReference(self::ENTITY_NAMESPACE . '\TestBusinessUnit', $businessUnitId);
    }

    public function testMonitoredEntityIsCreated()
    {
        $this->listener->addSupportedClass(self::ENTITY_NAMESPACE . '\TestUser', ['owner'], ['businessUnits']);

        $this->ownerTreeProvider->expects($this->once())
            ->method('clearCache');

        $this->setInsertQueryExpectation();

        $user = new TestUser();
        $this->em->persist($user);
        $this->em->flush();
    }

    public function testNotMonitoredEntityIsCreated()
    {
        $this->ownerTreeProvider->expects($this->never())
            ->method('clearCache');

        $this->setInsertQueryExpectation();

        $user = new TestUser();
        $this->em->persist($user);
        $this->em->flush();
    }

    public function testMonitoredEntityIsDeleted()
    {
        $this->listener->addSupportedClass(self::ENTITY_NAMESPACE . '\TestUser', ['owner'], ['businessUnits']);

        $this->ownerTreeProvider->expects($this->once())
            ->method('clearCache');

        $user = $this->findUser(1, 'test', 10);
        $this->em->remove($user);
        $this->em->flush();
    }

    public function testNotMonitoredEntityIsDeleted()
    {
        $this->ownerTreeProvider->expects($this->never())
            ->method('clearCache');

        $user = $this->findUser(1, 'test', 10);
        $this->em->remove($user);
        $this->em->flush();
    }

    public function testMonitoredToOneAssociationIsChanged()
    {
        $this->listener->addSupportedClass(self::ENTITY_NAMESPACE . '\TestUser', ['owner'], ['businessUnits']);

        $this->ownerTreeProvider->expects($this->once())
            ->method('clearCache');

        $user = $this->findUser(1, 'test', 10);
        $user->setOwner($this->getBusinessUnitReference(20));
        $this->em->flush();
    }

    public function testNotMonitoredToOneAssociationIsChanged()
    {
        $this->ownerTreeProvider->expects($this->never())
            ->method('clearCache');

        $user = $this->findUser(1, 'test', 10);
        $user->setOwner($this->getBusinessUnitReference(20));
        $this->em->flush();
    }

    public function testNotMonitoredToOneAssociationIsChangedForMonitoredEntity()
    {
        $this->listener->addSupportedClass(self::ENTITY_NAMESPACE . '\TestUser', [], ['businessUnits']);

        $this->ownerTreeProvider->expects($this->never())
            ->method('clearCache');

        $user = $this->findUser(1, 'test', 10);
        $user->setOwner($this->getBusinessUnitReference(20));
        $this->em->flush();
    }

    public function testMonitoredToOneAssociationIsSet()
    {
        $this->listener->addSupportedClass(self::ENTITY_NAMESPACE . '\TestUser', ['owner'], ['businessUnits']);

        $this->ownerTreeProvider->expects($this->once())
            ->method('clearCache');

        $user = $this->findUser(1, 'test', null);
        $user->setOwner($this->getBusinessUnitReference(10));
        $this->em->flush();
    }

    public function testNotMonitoredToOneAssociationIsSet()
    {
        $this->ownerTreeProvider->expects($this->never())
            ->method('clearCache');

        $user = $this->findUser(1, 'test', null);
        $user->setOwner($this->getBusinessUnitReference(10));
        $this->em->flush();
    }

    public function testMonitoredToOneAssociationIsUnset()
    {
        $this->listener->addSupportedClass(self::ENTITY_NAMESPACE . '\TestUser', ['owner'], ['businessUnits']);

        $this->ownerTreeProvider->expects($this->once())
            ->method('clearCache');

        $user = $this->findUser(1, 'test', 10);
        $user->setOwner(null);
        $this->em->flush();
    }

    public function testNotMonitoredToOneAssociationIsUnset()
    {
        $this->ownerTreeProvider->expects($this->never())
            ->method('clearCache');

        $user = $this->findUser(1, 'test', 10);
        $user->setOwner(null);
        $this->em->flush();
    }

    public function testNewItemIsAddedToMonitoredToManyAssociation()
    {
        $this->listener->addSupportedClass(self::ENTITY_NAMESPACE . '\TestUser', ['owner'], ['businessUnits']);

        $this->ownerTreeProvider->expects($this->once())
            ->method('clearCache');

        $this->addFindUserExpectation(1, 'test', 10);
        $this->addLoadUserBusinessUnitsExpectation(1, 10);
        $this->applyQueryExpectations($this->connection);

        $user = $this->em->getRepository(self::ENTITY_NAMESPACE . '\TestUser')->find(1);
        $user->addBusinessUnit($this->getBusinessUnitReference(20));
        $this->em->flush();
    }

    public function testNewItemIsAddedToNotMonitoredToManyAssociation()
    {
        $this->ownerTreeProvider->expects($this->never())
            ->method('clearCache');

        $this->addFindUserExpectation(1, 'test', 10);
        $this->addLoadUserBusinessUnitsExpectation(1, 10);
        $this->applyQueryExpectations($this->connection);

        $user = $this->em->getRepository(self::ENTITY_NAMESPACE . '\TestUser')->find(1);
        $user->addBusinessUnit($this->getBusinessUnitReference(20));
        $this->em->flush();
    }

    public function testNewItemIsAddedToNotMonitoredToManyAssociationForMonitoredEntity()
    {
        $this->listener->addSupportedClass(self::ENTITY_NAMESPACE . '\TestUser', ['owner'], ['organizations']);

        $this->ownerTreeProvider->expects($this->never())
            ->method('clearCache');

        $this->addFindUserExpectation(1, 'test', 10);
        $this->addLoadUserBusinessUnitsExpectation(1, 10);
        $this->applyQueryExpectations($this->connection);

        $user = $this->em->getRepository(self::ENTITY_NAMESPACE . '\TestUser')->find(1);
        $user->addBusinessUnit($this->getBusinessUnitReference(20));
        $this->em->flush();
    }

    public function testItemRemovedFromMonitoredToManyAssociation()
    {
        $this->listener->addSupportedClass(self::ENTITY_NAMESPACE . '\TestUser', ['owner'], ['businessUnits']);

        $this->ownerTreeProvider->expects($this->once())
            ->method('clearCache');

        $this->addFindUserExpectation(1, 'test', 10);
        $this->addLoadUserBusinessUnitsExpectation(1, 10);
        $this->applyQueryExpectations($this->connection);

        $user = $this->em->getRepository(self::ENTITY_NAMESPACE . '\TestUser')->find(1);
        $user->removeBusinessUnit($this->getBusinessUnitReference(10));
        $this->em->flush();
    }

    public function testItemRemovedFromNotMonitoredToManyAssociation()
    {
        $this->ownerTreeProvider->expects($this->never())
            ->method('clearCache');

        $this->addFindUserExpectation(1, 'test', 10);
        $this->addLoadUserBusinessUnitsExpectation(1, 10);
        $this->applyQueryExpectations($this->connection);

        $user = $this->em->getRepository(self::ENTITY_NAMESPACE . '\TestUser')->find(1);
        $user->removeBusinessUnit($this->getBusinessUnitReference(10));
        $this->em->flush();
    }

    public function testNotMonitoredFieldIsChanged()
    {
        $this->listener->addSupportedClass(self::ENTITY_NAMESPACE . '\TestUser', ['owner'], ['businessUnits']);

        $this->ownerTreeProvider->expects($this->never())
            ->method('clearCache');

        $user = $this->findUser(1, 'test', 10);
        $user->setUsername('new');
        $this->em->flush();
    }
}
