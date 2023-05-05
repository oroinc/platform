<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\EventListener;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Oro\Bundle\SecurityBundle\EventListener\OwnerTreeListener;
use Oro\Bundle\SecurityBundle\Owner\OwnerTreeProviderInterface;
use Oro\Bundle\SecurityBundle\Tests\Unit\Owner\Fixtures\Entity\TestBusinessUnit;
use Oro\Bundle\SecurityBundle\Tests\Unit\Owner\Fixtures\Entity\TestUser;
use Oro\Component\Testing\Unit\ORM\OrmTestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class OwnerTreeListenerTest extends OrmTestCase
{
    private const ENTITY_NAMESPACE = 'Oro\Bundle\SecurityBundle\Tests\Unit\Owner\Fixtures\Entity';

    /** @var EntityManagerInterface */
    private $em;

    /** @var OwnerTreeProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $ownerTreeProvider;

    /** @var OwnerTreeListener */
    private $listener;

    protected function setUp(): void
    {
        $this->em = $this->getTestEntityManager();
        $this->em->getConfiguration()->setMetadataDriverImpl(new AnnotationDriver(new AnnotationReader()));

        $this->ownerTreeProvider = $this->createMock(OwnerTreeProviderInterface::class);

        $this->listener = new OwnerTreeListener($this->ownerTreeProvider);
        $this->listener->addSupportedClass(self::ENTITY_NAMESPACE . '\TestOrganization');
        $this->em->getEventManager()->addEventListener('onFlush', $this->listener);
    }

    private function addInsertUserExpectation(?string $userName, ?int $businessUnitId): void
    {
        $this->addQueryExpectation(
            'INSERT INTO tbl_user (username, owner_id) VALUES (?, ?)',
            null,
            [1 => $userName, 2 => $businessUnitId],
            [1 => \PDO::PARAM_STR, 2 => \PDO::PARAM_INT],
            1
        );
    }

    private function addUpdateUserExpectation(int $userId, ?string $userName): void
    {
        $this->addQueryExpectation(
            'UPDATE tbl_user SET username = ? WHERE id = ?',
            null,
            [1 => $userName, 2 => $userId],
            [1 => \PDO::PARAM_STR, 2 => \PDO::PARAM_INT],
            1
        );
    }

    private function addDeleteUserExpectation(int $userId): void
    {
        $this->addQueryExpectation(
            'DELETE FROM tbl_user_to_business_unit WHERE user_id = ?',
            null,
            [1 => $userId],
            [1 => \PDO::PARAM_INT]
        );
        $this->addQueryExpectation(
            'DELETE FROM tbl_user_to_organization WHERE user_id = ?',
            null,
            [1 => $userId],
            [1 => \PDO::PARAM_INT]
        );
        $this->addQueryExpectation(
            'DELETE FROM tbl_user WHERE id = ?',
            null,
            [1 => $userId],
            [1 => \PDO::PARAM_INT]
        );
    }

    private function addDeleteUserToBusinessUnitAssociationExpectation(int $userId, int $businessUnitId): void
    {
        $this->addQueryExpectation(
            'DELETE FROM tbl_user_to_business_unit WHERE user_id = ? AND business_unit_id = ?',
            null,
            [1 => $userId, 2 => $businessUnitId],
            [1 => \PDO::PARAM_INT, 2 => \PDO::PARAM_INT]
        );
    }

    private function addAssignUserToBusinessUnitExpectation(int $userId, int $businessUnitId): void
    {
        $this->addQueryExpectation(
            'INSERT INTO tbl_user_to_business_unit (user_id, business_unit_id) VALUES (?, ?)',
            null,
            [1 => $userId, 2 => $businessUnitId],
            [1 => \PDO::PARAM_INT, 2 => \PDO::PARAM_INT],
            1
        );
    }

    private function addUpdateUserOwnerExpectation(int $userId, ?int $businessUnitId): void
    {
        $this->addQueryExpectation(
            'UPDATE tbl_user SET owner_id = ? WHERE id = ?',
            null,
            [1 => $businessUnitId, 2 => $userId],
            [1 => \PDO::PARAM_INT, 2 => \PDO::PARAM_INT],
            1
        );
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

        $this->addInsertUserExpectation(null, null);
        $this->applyQueryExpectations($this->getDriverConnectionMock($this->em));

        $user = new TestUser();
        $this->em->persist($user);
        $this->em->flush();
    }

    public function testNotMonitoredEntityIsCreated()
    {
        $this->ownerTreeProvider->expects($this->never())
            ->method('clearCache');

        $this->addInsertUserExpectation(null, null);
        $this->applyQueryExpectations($this->getDriverConnectionMock($this->em));

        $user = new TestUser();
        $this->em->persist($user);
        $this->em->flush();
    }

    public function testMonitoredEntityIsDeleted()
    {
        $userId = 1;

        $this->listener->addSupportedClass(self::ENTITY_NAMESPACE . '\TestUser', ['owner'], ['businessUnits']);

        $this->ownerTreeProvider->expects($this->once())
            ->method('clearCache');

        $this->addFindUserExpectation($userId, 'test', 10);
        $this->addDeleteUserExpectation($userId);
        $this->applyQueryExpectations($this->getDriverConnectionMock($this->em));

        $user = $this->em->getRepository(self::ENTITY_NAMESPACE . '\TestUser')->find($userId);
        $this->em->remove($user);
        $this->em->flush();
    }

    public function testNotMonitoredEntityIsDeleted()
    {
        $userId = 1;

        $this->ownerTreeProvider->expects($this->never())
            ->method('clearCache');

        $this->addFindUserExpectation($userId, 'test', 10);
        $this->addDeleteUserExpectation($userId);
        $this->applyQueryExpectations($this->getDriverConnectionMock($this->em));

        $user = $this->em->getRepository(self::ENTITY_NAMESPACE . '\TestUser')->find($userId);
        $this->em->remove($user);
        $this->em->flush();
    }

    public function testMonitoredToOneAssociationIsChanged()
    {
        $userId = 1;
        $businessUnitId = 20;

        $this->listener->addSupportedClass(self::ENTITY_NAMESPACE . '\TestUser', ['owner'], ['businessUnits']);

        $this->ownerTreeProvider->expects($this->once())
            ->method('clearCache');

        $this->addFindUserExpectation($userId, 'test', 10);
        $this->addUpdateUserOwnerExpectation($userId, $businessUnitId);
        $this->applyQueryExpectations($this->getDriverConnectionMock($this->em));

        $user = $this->em->getRepository(self::ENTITY_NAMESPACE . '\TestUser')->find($userId);
        $user->setOwner($this->getBusinessUnitReference($businessUnitId));
        $this->em->flush();
    }

    public function testNotMonitoredToOneAssociationIsChanged()
    {
        $userId = 1;
        $businessUnitId = 20;

        $this->ownerTreeProvider->expects($this->never())
            ->method('clearCache');

        $this->addFindUserExpectation($userId, 'test', 10);
        $this->addUpdateUserOwnerExpectation($userId, $businessUnitId);
        $this->applyQueryExpectations($this->getDriverConnectionMock($this->em));

        $user = $this->em->getRepository(self::ENTITY_NAMESPACE . '\TestUser')->find($userId);
        $user->setOwner($this->getBusinessUnitReference($businessUnitId));
        $this->em->flush();
    }

    public function testNotMonitoredToOneAssociationIsChangedForMonitoredEntity()
    {
        $userId = 1;
        $businessUnitId = 20;

        $this->listener->addSupportedClass(self::ENTITY_NAMESPACE . '\TestUser', [], ['businessUnits']);

        $this->ownerTreeProvider->expects($this->never())
            ->method('clearCache');

        $this->addFindUserExpectation($userId, 'test', 10);
        $this->addUpdateUserOwnerExpectation($userId, $businessUnitId);
        $this->applyQueryExpectations($this->getDriverConnectionMock($this->em));

        $user = $this->em->getRepository(self::ENTITY_NAMESPACE . '\TestUser')->find($userId);
        $user->setOwner($this->getBusinessUnitReference($businessUnitId));
        $this->em->flush();
    }

    public function testMonitoredToOneAssociationIsSet()
    {
        $userId = 1;
        $businessUnitId = 10;

        $this->listener->addSupportedClass(self::ENTITY_NAMESPACE . '\TestUser', ['owner'], ['businessUnits']);

        $this->ownerTreeProvider->expects($this->once())
            ->method('clearCache');

        $this->addFindUserExpectation($userId, 'test', null);
        $this->addUpdateUserOwnerExpectation($userId, $businessUnitId);
        $this->applyQueryExpectations($this->getDriverConnectionMock($this->em));

        $user = $this->em->getRepository(self::ENTITY_NAMESPACE . '\TestUser')->find($userId);
        $user->setOwner($this->getBusinessUnitReference($businessUnitId));
        $this->em->flush();
    }

    public function testNotMonitoredToOneAssociationIsSet()
    {
        $userId = 1;

        $this->ownerTreeProvider->expects($this->never())
            ->method('clearCache');

        $this->addFindUserExpectation($userId, 'test', 10);
        $this->applyQueryExpectations($this->getDriverConnectionMock($this->em));

        $user = $this->em->getRepository(self::ENTITY_NAMESPACE . '\TestUser')->find($userId);
        $user->setOwner($this->getBusinessUnitReference(10));
        $this->em->flush();
    }

    public function testMonitoredToOneAssociationIsUnset()
    {
        $userId = 1;

        $this->listener->addSupportedClass(self::ENTITY_NAMESPACE . '\TestUser', ['owner'], ['businessUnits']);

        $this->ownerTreeProvider->expects($this->once())
            ->method('clearCache');

        $this->addFindUserExpectation($userId, 'test', 10);
        $this->addUpdateUserOwnerExpectation($userId, null);
        $this->applyQueryExpectations($this->getDriverConnectionMock($this->em));

        $user = $this->em->getRepository(self::ENTITY_NAMESPACE . '\TestUser')->find($userId);
        $user->setOwner(null);
        $this->em->flush();
    }

    public function testNotMonitoredToOneAssociationIsUnset()
    {
        $userId = 1;

        $this->ownerTreeProvider->expects($this->never())
            ->method('clearCache');

        $this->addFindUserExpectation($userId, 'test', 10);
        $this->addUpdateUserOwnerExpectation($userId, null);
        $this->applyQueryExpectations($this->getDriverConnectionMock($this->em));

        $user = $this->em->getRepository(self::ENTITY_NAMESPACE . '\TestUser')->find($userId);
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
        $this->addAssignUserToBusinessUnitExpectation(1, 20);
        $this->applyQueryExpectations($this->getDriverConnectionMock($this->em));

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
        $this->addAssignUserToBusinessUnitExpectation(1, 20);
        $this->applyQueryExpectations($this->getDriverConnectionMock($this->em));

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
        $this->addAssignUserToBusinessUnitExpectation(1, 20);
        $this->applyQueryExpectations($this->getDriverConnectionMock($this->em));

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
        $this->addDeleteUserToBusinessUnitAssociationExpectation(1, 10);
        $this->applyQueryExpectations($this->getDriverConnectionMock($this->em));

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
        $this->addDeleteUserToBusinessUnitAssociationExpectation(1, 10);
        $this->applyQueryExpectations($this->getDriverConnectionMock($this->em));

        $user = $this->em->getRepository(self::ENTITY_NAMESPACE . '\TestUser')->find(1);
        $user->removeBusinessUnit($this->getBusinessUnitReference(10));
        $this->em->flush();
    }

    public function testNotMonitoredFieldIsChanged()
    {
        $userId = 1;
        $newUserName = 'new';

        $this->listener->addSupportedClass(self::ENTITY_NAMESPACE . '\TestUser', ['owner'], ['businessUnits']);

        $this->ownerTreeProvider->expects($this->never())
            ->method('clearCache');

        $this->addFindUserExpectation($userId, 'test', 10);
        $this->addUpdateUserExpectation($userId, $newUserName);
        $this->applyQueryExpectations($this->getDriverConnectionMock($this->em));

        $user = $this->em->getRepository(self::ENTITY_NAMESPACE . '\TestUser')->find($userId);
        $user->setUsername($newUserName);
        $this->em->flush();
    }
}
