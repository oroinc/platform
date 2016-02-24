<?php

namespace Oro\Bundle\OrganizationBundle\Tests\Ownership;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\OrganizationBundle\Ownership\OwnerDeletionManager;
use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdAccessor;
use Oro\Bundle\OrganizationBundle\Tests\Unit\Ownership\Fixture\Entity\TestEntity;
use Oro\Bundle\OrganizationBundle\Tests\Unit\Ownership\Fixture\Entity\TestOwnerEntity;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadata;

class OwnerDeletionManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var OwnerDeletionManager */
    protected $ownerDeletionManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $defaultChecker;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $ownershipProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $ownershipMetadata;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $em;

    protected function setUp()
    {
        $this->defaultChecker    =
            $this->getMock('Oro\Bundle\OrganizationBundle\Ownership\OwnerAssignmentCheckerInterface');
        $this->ownershipProvider =
            $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
                ->disableOriginalConstructor()
                ->getMock();
        $this->ownershipMetadata =
            $this->getMockBuilder('Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProvider')
                ->disableOriginalConstructor()
                ->getMock();
        $this->em                =
            $this->getMockBuilder('\Doctrine\ORM\EntityManager')
                ->disableOriginalConstructor()
                ->getMock();

        $this->ownershipMetadata->expects($this->any())
            ->method('getUserClass')
            ->will(
                $this->returnValue('Oro\Bundle\OrganizationBundle\Tests\Unit\Ownership\Fixture\Entity\TestOwnerEntity')
            );

        $doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->ownerDeletionManager = new OwnerDeletionManager(
            $this->defaultChecker,
            $this->ownershipProvider,
            $this->ownershipMetadata,
            $this->em,
            new ObjectIdAccessor($doctrineHelper)
        );
    }

    public function testIsOwner()
    {
        $this->assertEquals(
            true,
            $this->ownerDeletionManager->isOwner(new TestOwnerEntity())
        );
        $this->assertEquals(
            false,
            $this->ownerDeletionManager->isOwner(new TestEntity())
        );
    }

    public function testHasAssignmentsForNotOwnerEntity()
    {
        $owner = new TestEntity();

        $this->ownershipProvider->expects($this->never())
            ->method('getConfigs');

        $result = $this->ownerDeletionManager->hasAssignments($owner);
        $this->assertEquals(false, $result);
    }

    public function testHasAssignmentsForNonOwnerTypeEntity()
    {
        $owner = new TestOwnerEntity();

        $entity          = new TestEntity();
        $entityClassName = get_class($entity);
        $entityOwnerType = 'NONE';

        $entityConfig = $this->getEntityConfig(
            $entityClassName,
            [
                'owner_type' => $entityOwnerType,
            ]
        );

        $this->ownershipProvider->expects($this->once())
            ->method('getConfigs')
            ->with(null, true)
            ->will($this->returnValue([$entityConfig]));

        $this->ownershipMetadata->expects($this->never())
            ->method('getMetadata');

        $result = $this->ownerDeletionManager->hasAssignments($owner);
        $this->assertEquals(false, $result);
    }

    public function testHasAssignmentsWithDefaultChecker()
    {
        $owner   = new TestOwnerEntity();
        $ownerId = 123;
        $owner->setId($ownerId);

        $entity          = new TestEntity();
        $entityClassName = get_class($entity);
        $entityOwnerType = 'USER';

        $entityConfig            = $this->getEntityConfig(
            $entityClassName,
            [
                'owner_type' => $entityOwnerType,
            ]
        );
        $entityOwnershipMetadata = new OwnershipMetadata($entityOwnerType, 'owner', 'owner_id');

        $this->ownershipProvider->expects($this->once())
            ->method('getConfigs')
            ->with(null, true)
            ->will($this->returnValue([$entityConfig]));

        $this->ownershipMetadata->expects($this->once())
            ->method('getMetadata')
            ->with($entityClassName)
            ->will($this->returnValue($entityOwnershipMetadata));

        $this->defaultChecker->expects($this->once())
            ->method('hasAssignments')
            ->with($ownerId, $entityClassName, 'owner', $this->identicalTo($this->em))
            ->will($this->returnValue(true));

        $result = $this->ownerDeletionManager->hasAssignments($owner);
        $this->assertEquals(true, $result);
    }

    public function testHasAssignmentsWithCustomChecker()
    {
        $owner   = new TestOwnerEntity();
        $ownerId = 123;
        $owner->setId($ownerId);

        $entity          = new TestEntity();
        $entityClassName = get_class($entity);
        $entityOwnerType = 'USER';

        $entityConfig            = $this->getEntityConfig(
            $entityClassName,
            [
                'owner_type' => $entityOwnerType,
            ]
        );
        $entityOwnershipMetadata = new OwnershipMetadata($entityOwnerType, 'owner', 'owner_id');

        $this->ownershipProvider->expects($this->once())
            ->method('getConfigs')
            ->with(null, true)
            ->will($this->returnValue([$entityConfig]));

        $this->ownershipMetadata->expects($this->once())
            ->method('getMetadata')
            ->with($entityClassName)
            ->will($this->returnValue($entityOwnershipMetadata));

        $customChecker =
            $this->getMock('Oro\Bundle\OrganizationBundle\Ownership\OwnerAssignmentCheckerInterface');
        $customChecker->expects($this->once())
            ->method('hasAssignments')
            ->with($ownerId, $entityClassName, 'owner', $this->identicalTo($this->em))
            ->will($this->returnValue(true));

        $this->defaultChecker->expects($this->never())
            ->method('hasAssignments');

        $this->ownerDeletionManager->registerAssignmentChecker($entityClassName, $customChecker);
        $result = $this->ownerDeletionManager->hasAssignments($owner);
        $this->assertEquals(true, $result);
    }

    protected function getEntityConfig($entityClassName, $values)
    {
        $entityConfigId = new EntityConfigId('entity', $entityClassName);
        $entityConfig   = new Config($entityConfigId);
        $entityConfig->setValues($values);

        return $entityConfig;
    }
}
