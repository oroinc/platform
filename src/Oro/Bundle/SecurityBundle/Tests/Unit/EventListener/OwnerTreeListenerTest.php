<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\EventListener;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Component\TestUtils\ORM\Mocks\EntityManagerMock;
use Oro\Component\TestUtils\ORM\OrmTestCase;
use Oro\Bundle\SecurityBundle\EventListener\OwnerTreeListener;
use Oro\Bundle\SecurityBundle\Tests\Unit\Owner\Fixtures\Entity\TestBusinessUnit;
use Oro\Bundle\SecurityBundle\Tests\Unit\Owner\Fixtures\Entity\TestUser;

class OwnerTreeListenerTest extends OrmTestCase
{
    const ENTITY_NAMESPACE = 'Oro\Bundle\SecurityBundle\Tests\Unit\Owner\Fixtures\Entity';

    /** @var EntityManagerMock */
    protected $em;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ContainerInterface */
    protected $conn;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ContainerInterface */
    protected $treeProvider;

    /** @var OwnerTreeListener */
    protected $listener;

    protected function setUp()
    {
        $reader = new AnnotationReader();
        $metadataDriver = new AnnotationDriver($reader, self::ENTITY_NAMESPACE);

        $this->em = $this->getTestEntityManager();
        $this->em->getConfiguration()->setMetadataDriverImpl($metadataDriver);
        $this->em->getConfiguration()->setEntityNamespaces(['Test' => self::ENTITY_NAMESPACE]);

        $doctrine = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();
        $doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->will($this->returnValue($this->em));

        $this->conn = $this->getDriverConnectionMock($this->em);

        $this->treeProvider = $this->getMock('Oro\Bundle\SecurityBundle\Owner\OwnerTreeProviderInterface');

        $container = $this->getMock('Symfony\Component\DependencyInjection\ContainerInterface');
        $container->expects($this->any())
            ->method('get')
            ->with('oro_security.ownership_tree_provider.chain')
            ->willReturn($this->treeProvider);

        $this->listener = new OwnerTreeListener();
        $this->listener->setContainer($container);
        $this->listener->addSupportedClass(self::ENTITY_NAMESPACE . '\TestOrganization');
        $this->em->getEventManager()->addEventListener('onFlush', $this->listener);
    }

    /**
     * @param int    $expectsAt
     * @param string $sql
     * @param array  $result
     * @param array  $params
     * @param array  $types
     */
    protected function setQueryExpectationAt($expectsAt, $sql, $result, $params = [], $types = [])
    {
        $stmt = $this->createFetchStatementMock($result, $params, $types);
        if ($params) {
            $this->conn->expects($this->at($expectsAt))
                ->method('prepare')
                ->with($sql)
                ->will($this->returnValue($stmt));
        } else {
            $this->conn->expects($this->at($expectsAt))
                ->method('query')
                ->with($sql)
                ->will($this->returnValue($stmt));
        }
    }

    protected function setInsertQueryExpectation()
    {
        $this->conn->expects($this->once())
            ->method('prepare')
            ->will($this->returnValue($this->getMock('Oro\Component\TestUtils\ORM\Mocks\StatementMock')));
    }

    /**
     * @param int      $userId
     * @param string   $userName
     * @param int|null $ownerId
     *
     * @return TestUser
     */
    protected function findUser($userId, $userName, $ownerId)
    {
        $this->setQueryExpectationAt(
            0,
            'SELECT t0.id AS id_1, t0.username AS username_2, t0.owner_id AS owner_id_3'
            . ' FROM tbl_user t0 WHERE t0.id = ?',
            [['id_1' => $userId, 'username_2' => $userName, 'owner_id_3' => $ownerId]],
            [1 => $userId],
            [1 => \PDO::PARAM_INT]
        );

        return $this->em->getRepository(self::ENTITY_NAMESPACE . '\TestUser')->find($userId);
    }

    /**
     * @param int      $userId
     * @param int|null $businessUnitId
     */
    protected function setLoadUserBusinessUnitsExpectation($userId, $businessUnitId)
    {
        $rows = [];
        if (null !== $businessUnitId) {
            $rows[] = ['id_1' => $businessUnitId, 'parent_id_2' => null, 'organization_id_3' => null];
        }
        $this->setQueryExpectationAt(
            1,
            'SELECT t0.id AS id_1, t0.parent_id AS parent_id_2, t0.organization_id AS organization_id_3'
            . ' FROM tbl_business_unit t0'
            . ' INNER JOIN tbl_user_to_business_unit ON t0.id = tbl_user_to_business_unit.business_unit_id'
            . ' WHERE tbl_user_to_business_unit.user_id = ?',
            $rows,
            [1 => $userId],
            [1 => \PDO::PARAM_INT]
        );
    }

    /**
     * @param int $businessUnitId
     *
     * @return TestBusinessUnit
     */
    protected function getBusinessUnitReference($businessUnitId)
    {
        return $this->em->getReference(self::ENTITY_NAMESPACE . '\TestBusinessUnit', $businessUnitId);
    }

    public function testMonitoredEntityIsCreated()
    {
        $this->listener->addSupportedClass(self::ENTITY_NAMESPACE . '\TestUser', ['owner'], ['businessUnits']);

        $this->treeProvider->expects($this->once())
            ->method('clear');

        $this->setInsertQueryExpectation();

        $user = new TestUser();
        $this->em->persist($user);
        $this->em->flush();
    }

    public function testNotMonitoredEntityIsCreated()
    {
        $this->treeProvider->expects($this->never())
            ->method('clear');

        $this->setInsertQueryExpectation();

        $user = new TestUser();
        $this->em->persist($user);
        $this->em->flush();
    }

    public function testMonitoredEntityIsDeleted()
    {
        $this->listener->addSupportedClass(self::ENTITY_NAMESPACE . '\TestUser', ['owner'], ['businessUnits']);

        $this->treeProvider->expects($this->once())
            ->method('clear');

        $user = $this->findUser(1, 'test', 10);
        $this->em->remove($user);
        $this->em->flush();
    }

    public function testNotMonitoredEntityIsDeleted()
    {
        $this->treeProvider->expects($this->never())
            ->method('clear');

        $user = $this->findUser(1, 'test', 10);
        $this->em->remove($user);
        $this->em->flush();
    }

    public function testMonitoredToOneAssociationIsChanged()
    {
        $this->listener->addSupportedClass(self::ENTITY_NAMESPACE . '\TestUser', ['owner'], ['businessUnits']);

        $this->treeProvider->expects($this->once())
            ->method('clear');

        $user = $this->findUser(1, 'test', 10);
        $user->setOwner($this->getBusinessUnitReference(20));
        $this->em->flush();
    }

    public function testNotMonitoredToOneAssociationIsChanged()
    {
        $this->treeProvider->expects($this->never())
            ->method('clear');

        $user = $this->findUser(1, 'test', 10);
        $user->setOwner($this->getBusinessUnitReference(20));
        $this->em->flush();
    }

    public function testNotMonitoredToOneAssociationIsChangedForMonitoredEntity()
    {
        $this->listener->addSupportedClass(self::ENTITY_NAMESPACE . '\TestUser', [], ['businessUnits']);

        $this->treeProvider->expects($this->never())
            ->method('clear');

        $user = $this->findUser(1, 'test', 10);
        $user->setOwner($this->getBusinessUnitReference(20));
        $this->em->flush();
    }

    public function testMonitoredToOneAssociationIsSet()
    {
        $this->listener->addSupportedClass(self::ENTITY_NAMESPACE . '\TestUser', ['owner'], ['businessUnits']);

        $this->treeProvider->expects($this->once())
            ->method('clear');

        $user = $this->findUser(1, 'test', null);
        $user->setOwner($this->getBusinessUnitReference(10));
        $this->em->flush();
    }

    public function testNotMonitoredToOneAssociationIsSet()
    {
        $this->treeProvider->expects($this->never())
            ->method('clear');

        $user = $this->findUser(1, 'test', null);
        $user->setOwner($this->getBusinessUnitReference(10));
        $this->em->flush();
    }

    public function testMonitoredToOneAssociationIsUnset()
    {
        $this->listener->addSupportedClass(self::ENTITY_NAMESPACE . '\TestUser', ['owner'], ['businessUnits']);

        $this->treeProvider->expects($this->once())
            ->method('clear');

        $user = $this->findUser(1, 'test', 10);
        $user->setOwner(null);
        $this->em->flush();
    }

    public function testNotMonitoredToOneAssociationIsUnset()
    {
        $this->treeProvider->expects($this->never())
            ->method('clear');

        $user = $this->findUser(1, 'test', 10);
        $user->setOwner(null);
        $this->em->flush();
    }

    public function testNewItemIsAddedToMonitoredToManyAssociation()
    {
        $this->listener->addSupportedClass(self::ENTITY_NAMESPACE . '\TestUser', ['owner'], ['businessUnits']);

        $this->treeProvider->expects($this->once())
            ->method('clear');

        $this->setLoadUserBusinessUnitsExpectation(1, 10);

        $user = $this->findUser(1, 'test', 10);
        $user->addBusinessUnit($this->getBusinessUnitReference(20));
        $this->em->flush();
    }

    public function testNewItemIsAddedToNotMonitoredToManyAssociation()
    {
        $this->treeProvider->expects($this->never())
            ->method('clear');

        $this->setLoadUserBusinessUnitsExpectation(1, 10);

        $user = $this->findUser(1, 'test', 10);
        $user->addBusinessUnit($this->getBusinessUnitReference(20));
        $this->em->flush();
    }

    public function testNewItemIsAddedToNotMonitoredToManyAssociationForMonitoredEntity()
    {
        $this->listener->addSupportedClass(self::ENTITY_NAMESPACE . '\TestUser', ['owner'], ['organizations']);

        $this->treeProvider->expects($this->never())
            ->method('clear');

        $this->setLoadUserBusinessUnitsExpectation(1, 10);

        $user = $this->findUser(1, 'test', 10);
        $user->addBusinessUnit($this->getBusinessUnitReference(20));
        $this->em->flush();
    }

    public function testItemRemovedFromMonitoredToManyAssociation()
    {
        $this->listener->addSupportedClass(self::ENTITY_NAMESPACE . '\TestUser', ['owner'], ['businessUnits']);

        $this->treeProvider->expects($this->once())
            ->method('clear');

        $this->setLoadUserBusinessUnitsExpectation(1, 10);

        $user = $this->findUser(1, 'test', 10);
        $user->removeBusinessUnit($this->getBusinessUnitReference(10));
        $this->em->flush();
    }

    public function testItemRemovedFromNotMonitoredToManyAssociation()
    {
        $this->treeProvider->expects($this->never())
            ->method('clear');

        $this->setLoadUserBusinessUnitsExpectation(1, 10);

        $user = $this->findUser(1, 'test', 10);
        $user->removeBusinessUnit($this->getBusinessUnitReference(10));
        $this->em->flush();
    }

    public function testNotMonitoredFieldIsChanged()
    {
        $this->listener->addSupportedClass(self::ENTITY_NAMESPACE . '\TestUser', ['owner'], ['businessUnits']);

        $this->treeProvider->expects($this->never())
            ->method('clear');

        $user = $this->findUser(1, 'test', 10);
        $user->setUsername('new');
        $this->em->flush();
    }
}
