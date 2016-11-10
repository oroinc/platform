<?php

namespace Oro\Bundle\OrganizationBundle\Tests\Unit\Validator\Constrains;

use Symfony\Component\Validator\Tests\Constraints\AbstractConstraintValidatorTest;

use Oro\Bundle\OrganizationBundle\Tests\Unit\Fixture\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Validator\Constraints\Owner;
use Oro\Bundle\OrganizationBundle\Validator\Constraints\OwnerValidator;
use Oro\Bundle\OrganizationBundle\Tests\Unit\Fixture\Entity\Entity;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadata;
use Oro\Bundle\OrganizationBundle\Tests\Unit\Fixture\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Tests\Unit\Fixture\Entity\User;

class OwnerValidatorTest extends AbstractConstraintValidatorTest
{
    const TEST_ENTITY_CLASS = 'Oro\Bundle\OrganizationBundle\Tests\Unit\Fixture\Entity\Entity';

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $doctrine;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $ownershipMetadataProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $entityOwnerAccessor;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $businessUnitManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $aclVoter;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $securityFacade;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $treeProvider;

    /** @var Entity */
    protected $testEntity;

    /** @var User */
    protected $currentUser;

    /** @var Organization */
    protected $currentOrg;

    protected function setUp()
    {
        $this->doctrine = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();
        $this->ownershipMetadataProvider = $this
            ->getMockBuilder('Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityOwnerAccessor = $this->getMockBuilder('Oro\Bundle\SecurityBundle\Owner\EntityOwnerAccessor')
            ->disableOriginalConstructor()
            ->getMock();
        $this->businessUnitManager = $this
            ->getMockBuilder('Oro\Bundle\OrganizationBundle\Entity\Manager\BusinessUnitManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->aclVoter = $this->getMockBuilder('Oro\Bundle\SecurityBundle\Acl\Voter\AclVoter')
            ->disableOriginalConstructor()
            ->getMock();
        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();
        $this->treeProvider = $this->getMockBuilder('Oro\Bundle\SecurityBundle\Owner\OwnerTreeProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->testEntity = new Entity();
        $this->currentUser = new User();
        $this->currentUser->setId(12);
        $this->currentOrg = new Organization();
        $this->currentOrg->setId(2);

        $this->securityFacade->expects($this->any())
            ->method('getLoggedUser')
            ->willReturn($this->currentUser);
        $this->securityFacade->expects($this->any())
            ->method('getLoggedUserId')
            ->willReturn($this->currentUser->getId());
        $this->securityFacade->expects($this->any())
            ->method('getOrganization')
            ->willReturn($this->currentOrg);

        parent::setUp();
    }

    /**
     * {@inheritdoc}
     */
    protected function createValidator()
    {
        return new OwnerValidator(
            $this->doctrine,
            $this->businessUnitManager,
            $this->ownershipMetadataProvider,
            $this->entityOwnerAccessor,
            $this->securityFacade,
            $this->treeProvider,
            $this->aclVoter
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function createContext()
    {
        $this->constraint = new Owner();
        $this->propertyPath = null;

        return parent::createContext();
    }

    public function testValidateOnNonSupportedEntity()
    {
        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(self::TEST_ENTITY_CLASS)
            ->willReturn(null);

        $this->ownershipMetadataProvider->expects($this->never())
            ->method('getMetadata');

        $this->validator->validate($this->testEntity, $this->constraint);
        $this->assertNoViolation();
    }

    public function testValidateOnNonAclProtectedEntity()
    {
        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(self::TEST_ENTITY_CLASS)
            ->willReturn($this->getObjectManagerMock());

        $ownershipMetadata = new OwnershipMetadata();
        $this->getOwnershipMetadataExpectation($ownershipMetadata);

        $this->entityOwnerAccessor->expects($this->never())
            ->method('getOwner');

        $this->validator->validate($this->testEntity, $this->constraint);
        $this->assertNoViolation();
    }

    public function testValidExistingEntityWithUserOwner()
    {
        $owner = new User();
        $owner->setId(123);

        $this->testEntity->setId(234);
        $this->testEntity->setOwner($owner);

        $this->getOwnershipMetadataExpectation(
            new OwnershipMetadata('USER', 'owner', 'owner', 'organization', 'organization')
        );

        $accessLevel = AccessLevel::DEEP_LEVEL;
        $this->addOneShotIsGrantedObserverExpectation($accessLevel);

        $classMetadata = $this->getClassMetadataMock();
        $this->getClassMetadataExpectation($classMetadata);

        $this->securityFacade->expects($this->once())
            ->method('isGranted')
            ->with('ASSIGN', $this->testEntity)
            ->willReturn(true);
        $classMetadata->expects($this->once())
            ->method('getIdentifierValues')
            ->with($this->testEntity)
            ->willReturn([$this->testEntity->getId()]);
        $this->businessUnitManager->expects($this->once())
            ->method('canUserBeSetAsOwner')
            ->with($this->currentUser, $owner, $accessLevel, $this->treeProvider, $this->currentOrg)
            ->willReturn(true);
        $this->entityOwnerAccessor->expects($this->once())
            ->method('getOwner')
            ->with($this->testEntity)
            ->willReturn($this->testEntity->getOwner());

        $this->validator->validate($this->testEntity, $this->constraint);
        $this->assertNoViolation();
    }

    public function testValidExistingEntityWithBusinessUnitOwner()
    {
        $owner = new BusinessUnit();
        $owner->setId(123);

        $this->testEntity->setId(234);
        $this->testEntity->setOwner($owner);

        $this->getOwnershipMetadataExpectation(
            new OwnershipMetadata('BUSINESS_UNIT', 'owner', 'owner', 'organization', 'organization')
        );

        $accessLevel = AccessLevel::DEEP_LEVEL;
        $this->addOneShotIsGrantedObserverExpectation($accessLevel);

        $classMetadata = $this->getClassMetadataMock();
        $this->getClassMetadataExpectation($classMetadata);

        $this->securityFacade->expects($this->once())
            ->method('isGranted')
            ->with('ASSIGN', $this->testEntity)
            ->willReturn(true);
        $classMetadata->expects($this->once())
            ->method('getIdentifierValues')
            ->with($this->testEntity)
            ->willReturn([$this->testEntity->getId()]);
        $this->businessUnitManager->expects($this->once())
            ->method('canBusinessUnitBeSetAsOwner')
            ->with($this->currentUser, $owner, $accessLevel, $this->treeProvider, $this->currentOrg)
            ->willReturn(true);
        $this->entityOwnerAccessor->expects($this->once())
            ->method('getOwner')
            ->with($this->testEntity)
            ->willReturn($this->testEntity->getOwner());

        $this->validator->validate($this->testEntity, $this->constraint);
        $this->assertNoViolation();
    }

    public function testValidExistingEntityWithOrganizationOwner()
    {
        $owner = new Organization();
        $owner->setId(123);

        $this->testEntity->setId(234);
        $this->testEntity->setOwner($owner);

        $this->getOwnershipMetadataExpectation(
            new OwnershipMetadata('ORGANIZATION', 'owner', 'owner', 'organization', 'organization')
        );

        $accessLevel = AccessLevel::DEEP_LEVEL;
        $this->addOneShotIsGrantedObserverExpectation($accessLevel);

        $classMetadata = $this->getClassMetadataMock();
        $this->getClassMetadataExpectation($classMetadata);

        $this->securityFacade->expects($this->once())
            ->method('isGranted')
            ->with('ASSIGN', $this->testEntity)
            ->willReturn(true);
        $classMetadata->expects($this->once())
            ->method('getIdentifierValues')
            ->with($this->testEntity)
            ->willReturn([$this->testEntity->getId()]);
        $this->getUserOrganizationIdsExpectation([2, 3, $owner->getId()]);
        $this->entityOwnerAccessor->expects($this->once())
            ->method('getOwner')
            ->with($this->testEntity)
            ->willReturn($this->testEntity->getOwner());

        $this->validator->validate($this->testEntity, $this->constraint);
        $this->assertNoViolation();
    }

    public function testInvalidExistingEntityWithUserOwner()
    {
        $owner = new User();
        $owner->setId(123);

        $this->testEntity->setId(234);
        $this->testEntity->setOwner($owner);

        $this->getOwnershipMetadataExpectation(
            new OwnershipMetadata('USER', 'owner', 'owner', 'organization', 'organization')
        );

        $accessLevel = AccessLevel::DEEP_LEVEL;
        $this->addOneShotIsGrantedObserverExpectation($accessLevel);

        $classMetadata = $this->getClassMetadataMock();
        $this->getClassMetadataExpectation($classMetadata);

        $this->securityFacade->expects($this->once())
            ->method('isGranted')
            ->with('ASSIGN', $this->testEntity)
            ->willReturn(true);
        $classMetadata->expects($this->once())
            ->method('getIdentifierValues')
            ->with($this->testEntity)
            ->willReturn([$this->testEntity->getId()]);
        $this->businessUnitManager->expects($this->once())
            ->method('canUserBeSetAsOwner')
            ->with($this->currentUser, $owner, $accessLevel, $this->treeProvider, $this->currentOrg)
            ->willReturn(false);
        $this->entityOwnerAccessor->expects($this->once())
            ->method('getOwner')
            ->with($this->testEntity)
            ->willReturn($this->testEntity->getOwner());

        $this->validator->validate($this->testEntity, $this->constraint);
        $this->buildViolation($this->constraint->message)
            ->atPath('owner')
            ->setParameters(['{{ owner }}' => 'owner'])
            ->assertRaised();
    }

    public function testInvalidExistingEntityWithBusinessUnitOwner()
    {
        $owner = new BusinessUnit();
        $owner->setId(123);

        $this->testEntity->setId(234);
        $this->testEntity->setOwner($owner);

        $this->getOwnershipMetadataExpectation(
            new OwnershipMetadata('BUSINESS_UNIT', 'owner', 'owner', 'organization', 'organization')
        );

        $accessLevel = AccessLevel::DEEP_LEVEL;
        $this->addOneShotIsGrantedObserverExpectation($accessLevel);

        $classMetadata = $this->getClassMetadataMock();
        $this->getClassMetadataExpectation($classMetadata);

        $this->securityFacade->expects($this->once())
            ->method('isGranted')
            ->with('ASSIGN', $this->testEntity)
            ->willReturn(true);
        $classMetadata->expects($this->once())
            ->method('getIdentifierValues')
            ->with($this->testEntity)
            ->willReturn([$this->testEntity->getId()]);
        $this->businessUnitManager->expects($this->once())
            ->method('canBusinessUnitBeSetAsOwner')
            ->with($this->currentUser, $owner, $accessLevel, $this->treeProvider, $this->currentOrg)
            ->willReturn(false);
        $this->entityOwnerAccessor->expects($this->once())
            ->method('getOwner')
            ->with($this->testEntity)
            ->willReturn($this->testEntity->getOwner());

        $this->validator->validate($this->testEntity, $this->constraint);
        $this->buildViolation($this->constraint->message)
            ->atPath('owner')
            ->setParameters(['{{ owner }}' => 'owner'])
            ->assertRaised();
    }

    public function testInvalidExistingEntityWithOrganizationOwner()
    {
        $owner = new Organization();
        $owner->setId(123);

        $this->testEntity->setId(234);
        $this->testEntity->setOwner($owner);

        $this->getOwnershipMetadataExpectation(
            new OwnershipMetadata('ORGANIZATION', 'owner', 'owner', 'organization', 'organization')
        );

        $accessLevel = AccessLevel::DEEP_LEVEL;
        $this->addOneShotIsGrantedObserverExpectation($accessLevel);

        $classMetadata = $this->getClassMetadataMock();
        $this->getClassMetadataExpectation($classMetadata);

        $this->securityFacade->expects($this->once())
            ->method('isGranted')
            ->with('ASSIGN', $this->testEntity)
            ->willReturn(true);
        $classMetadata->expects($this->once())
            ->method('getIdentifierValues')
            ->with($this->testEntity)
            ->willReturn([$this->testEntity->getId()]);
        $this->getUserOrganizationIdsExpectation([2, 3]);
        $this->entityOwnerAccessor->expects($this->once())
            ->method('getOwner')
            ->with($this->testEntity)
            ->willReturn($this->testEntity->getOwner());

        $this->validator->validate($this->testEntity, $this->constraint);
        $this->buildViolation($this->constraint->message)
            ->atPath('owner')
            ->setParameters(['{{ owner }}' => 'owner'])
            ->assertRaised();
    }

    public function testValidNewEntityWithUserOwner()
    {
        $owner = new User();
        $owner->setId(123);

        $this->testEntity->setOwner($owner);

        $this->getOwnershipMetadataExpectation(
            new OwnershipMetadata('USER', 'owner', 'owner', 'organization', 'organization')
        );

        $accessLevel = AccessLevel::DEEP_LEVEL;
        $this->addOneShotIsGrantedObserverExpectation($accessLevel);

        $classMetadata = $this->getClassMetadataMock();
        $this->getClassMetadataExpectation($classMetadata);

        $this->securityFacade->expects($this->once())
            ->method('isGranted')
            ->with('CREATE', 'entity:Oro\Bundle\OrganizationBundle\Tests\Unit\Fixture\Entity\Entity')
            ->willReturn(true);
        $classMetadata->expects($this->once())
            ->method('getIdentifierValues')
            ->with($this->testEntity)
            ->willReturn([]);
        $this->businessUnitManager->expects($this->once())
            ->method('canUserBeSetAsOwner')
            ->with($this->currentUser, $owner, $accessLevel, $this->treeProvider, $this->currentOrg)
            ->willReturn(true);
        $this->entityOwnerAccessor->expects($this->once())
            ->method('getOwner')
            ->with($this->testEntity)
            ->willReturn($this->testEntity->getOwner());

        $this->validator->validate($this->testEntity, $this->constraint);
        $this->assertNoViolation();
    }

    public function testValidNewEntityWithBusinessUnitOwner()
    {
        $owner = new BusinessUnit();
        $owner->setId(123);

        $this->testEntity->setOwner($owner);

        $this->getOwnershipMetadataExpectation(
            new OwnershipMetadata('BUSINESS_UNIT', 'owner', 'owner', 'organization', 'organization')
        );

        $accessLevel = AccessLevel::DEEP_LEVEL;
        $this->addOneShotIsGrantedObserverExpectation($accessLevel);

        $classMetadata = $this->getClassMetadataMock();
        $this->getClassMetadataExpectation($classMetadata);

        $this->securityFacade->expects($this->once())
            ->method('isGranted')
            ->with('CREATE', 'entity:Oro\Bundle\OrganizationBundle\Tests\Unit\Fixture\Entity\Entity')
            ->willReturn(true);
        $classMetadata->expects($this->once())
            ->method('getIdentifierValues')
            ->with($this->testEntity)
            ->willReturn([]);
        $this->businessUnitManager->expects($this->once())
            ->method('canBusinessUnitBeSetAsOwner')
            ->with($this->currentUser, $owner, $accessLevel, $this->treeProvider, $this->currentOrg)
            ->willReturn(true);
        $this->entityOwnerAccessor->expects($this->once())
            ->method('getOwner')
            ->with($this->testEntity)
            ->willReturn($this->testEntity->getOwner());

        $this->validator->validate($this->testEntity, $this->constraint);
        $this->assertNoViolation();
    }

    public function testValidNewEntityWithOrganizationOwner()
    {
        $owner = new Organization();
        $owner->setId(123);

        $this->testEntity->setOwner($owner);

        $this->getOwnershipMetadataExpectation(
            new OwnershipMetadata('ORGANIZATION', 'owner', 'owner', 'organization', 'organization')
        );

        $accessLevel = AccessLevel::DEEP_LEVEL;
        $this->addOneShotIsGrantedObserverExpectation($accessLevel);

        $classMetadata = $this->getClassMetadataMock();
        $this->getClassMetadataExpectation($classMetadata);

        $this->securityFacade->expects($this->once())
            ->method('isGranted')
            ->with('CREATE', 'entity:Oro\Bundle\OrganizationBundle\Tests\Unit\Fixture\Entity\Entity')
            ->willReturn(true);
        $classMetadata->expects($this->once())
            ->method('getIdentifierValues')
            ->with($this->testEntity)
            ->willReturn([]);
        $this->getUserOrganizationIdsExpectation([2, 3, $owner->getId()]);
        $this->entityOwnerAccessor->expects($this->once())
            ->method('getOwner')
            ->with($this->testEntity)
            ->willReturn($this->testEntity->getOwner());

        $this->validator->validate($this->testEntity, $this->constraint);
        $this->assertNoViolation();
    }

    public function testInvalidNewEntityWithUserOwner()
    {
        $owner = new User();
        $owner->setId(123);

        $this->testEntity->setOwner($owner);

        $this->getOwnershipMetadataExpectation(
            new OwnershipMetadata('USER', 'owner', 'owner', 'organization', 'organization')
        );

        $accessLevel = AccessLevel::DEEP_LEVEL;
        $this->addOneShotIsGrantedObserverExpectation($accessLevel);

        $classMetadata = $this->getClassMetadataMock();
        $this->getClassMetadataExpectation($classMetadata);

        $this->securityFacade->expects($this->once())
            ->method('isGranted')
            ->with('CREATE', 'entity:Oro\Bundle\OrganizationBundle\Tests\Unit\Fixture\Entity\Entity')
            ->willReturn(true);
        $classMetadata->expects($this->once())
            ->method('getIdentifierValues')
            ->with($this->testEntity)
            ->willReturn([]);
        $this->businessUnitManager->expects($this->once())
            ->method('canUserBeSetAsOwner')
            ->with($this->currentUser, $owner, $accessLevel, $this->treeProvider, $this->currentOrg)
            ->willReturn(false);
        $this->entityOwnerAccessor->expects($this->once())
            ->method('getOwner')
            ->with($this->testEntity)
            ->willReturn($this->testEntity->getOwner());

        $this->validator->validate($this->testEntity, $this->constraint);
        $this->buildViolation($this->constraint->message)
            ->atPath('owner')
            ->setParameters(['{{ owner }}' => 'owner'])
            ->assertRaised();
    }

    public function testInvalidNewEntityWithBusinessUnitOwner()
    {
        $owner = new BusinessUnit();
        $owner->setId(123);

        $this->testEntity->setOwner($owner);

        $this->getOwnershipMetadataExpectation(
            new OwnershipMetadata('BUSINESS_UNIT', 'owner', 'owner', 'organization', 'organization')
        );

        $accessLevel = AccessLevel::DEEP_LEVEL;
        $this->addOneShotIsGrantedObserverExpectation($accessLevel);

        $classMetadata = $this->getClassMetadataMock();
        $this->getClassMetadataExpectation($classMetadata);

        $this->securityFacade->expects($this->once())
            ->method('isGranted')
            ->with('CREATE', 'entity:Oro\Bundle\OrganizationBundle\Tests\Unit\Fixture\Entity\Entity')
            ->willReturn(true);
        $classMetadata->expects($this->once())
            ->method('getIdentifierValues')
            ->with($this->testEntity)
            ->willReturn([]);
        $this->businessUnitManager->expects($this->once())
            ->method('canBusinessUnitBeSetAsOwner')
            ->with($this->currentUser, $owner, $accessLevel, $this->treeProvider, $this->currentOrg)
            ->willReturn(false);
        $this->entityOwnerAccessor->expects($this->once())
            ->method('getOwner')
            ->with($this->testEntity)
            ->willReturn($this->testEntity->getOwner());

        $this->validator->validate($this->testEntity, $this->constraint);
        $this->buildViolation($this->constraint->message)
            ->atPath('owner')
            ->setParameters(['{{ owner }}' => 'owner'])
            ->assertRaised();
    }

    public function testInvalidNewEntityWithOrganizationOwner()
    {
        $owner = new Organization();
        $owner->setId(123);

        $this->testEntity->setOwner($owner);

        $this->getOwnershipMetadataExpectation(
            new OwnershipMetadata('ORGANIZATION', 'owner', 'owner', 'organization', 'organization')
        );

        $accessLevel = AccessLevel::DEEP_LEVEL;
        $this->addOneShotIsGrantedObserverExpectation($accessLevel);

        $classMetadata = $this->getClassMetadataMock();
        $this->getClassMetadataExpectation($classMetadata);

        $this->securityFacade->expects($this->once())
            ->method('isGranted')
            ->with('CREATE', 'entity:Oro\Bundle\OrganizationBundle\Tests\Unit\Fixture\Entity\Entity')
            ->willReturn(true);
        $classMetadata->expects($this->once())
            ->method('getIdentifierValues')
            ->with($this->testEntity)
            ->willReturn([]);
        $this->getUserOrganizationIdsExpectation([2, 3]);
        $this->entityOwnerAccessor->expects($this->once())
            ->method('getOwner')
            ->with($this->testEntity)
            ->willReturn($this->testEntity->getOwner());

        $this->validator->validate($this->testEntity, $this->constraint);
        $this->buildViolation($this->constraint->message)
            ->atPath('owner')
            ->setParameters(['{{ owner }}' => 'owner'])
            ->assertRaised();
    }

    public function testValidNewEntityWithNewUserOwner()
    {
        $owner = new User();
        $owner->setOrganization($this->currentOrg);

        $this->testEntity->setOwner($owner);
        $this->testEntity->setOrganization($this->currentOrg);

        $this->getOwnershipMetadataExpectation(
            new OwnershipMetadata('USER', 'owner', 'owner', 'organization', 'organization')
        );

        $accessLevel = AccessLevel::DEEP_LEVEL;
        $this->addOneShotIsGrantedObserverExpectation($accessLevel);

        $classMetadata = $this->getClassMetadataMock();
        $this->getClassMetadataExpectation($classMetadata);

        $this->securityFacade->expects($this->once())
            ->method('isGranted')
            ->with('CREATE', 'entity:Oro\Bundle\OrganizationBundle\Tests\Unit\Fixture\Entity\Entity')
            ->willReturn(true);
        $classMetadata->expects($this->once())
            ->method('getIdentifierValues')
            ->with($this->testEntity)
            ->willReturn([]);
        $this->businessUnitManager->expects($this->never())
            ->method('canUserBeSetAsOwner');
        $this->entityOwnerAccessor->expects($this->once())
            ->method('getOwner')
            ->with($this->testEntity)
            ->willReturn($this->testEntity->getOwner());
        $this->entityOwnerAccessor->expects($this->exactly(2))
            ->method('getOrganization')
            ->willReturnMap(
                [
                    [$owner, $owner->getOrganization()],
                    [$this->testEntity, $this->testEntity->getOrganization()],
                ]
            );

        $this->validator->validate($this->testEntity, $this->constraint);
        $this->assertNoViolation();
    }

    public function testValidNewEntityWithNewBusinessUnitOwner()
    {
        $owner = new BusinessUnit();
        $owner->setOrganization($this->currentOrg);

        $this->testEntity->setOwner($owner);
        $this->testEntity->setOrganization($this->currentOrg);

        $this->getOwnershipMetadataExpectation(
            new OwnershipMetadata('BUSINESS_UNIT', 'owner', 'owner', 'organization', 'organization')
        );

        $accessLevel = AccessLevel::DEEP_LEVEL;
        $this->addOneShotIsGrantedObserverExpectation($accessLevel);

        $classMetadata = $this->getClassMetadataMock();
        $this->getClassMetadataExpectation($classMetadata);

        $this->securityFacade->expects($this->once())
            ->method('isGranted')
            ->with('CREATE', 'entity:Oro\Bundle\OrganizationBundle\Tests\Unit\Fixture\Entity\Entity')
            ->willReturn(true);
        $classMetadata->expects($this->once())
            ->method('getIdentifierValues')
            ->with($this->testEntity)
            ->willReturn([]);
        $this->businessUnitManager->expects($this->never())
            ->method('canBusinessUnitBeSetAsOwner');
        $this->entityOwnerAccessor->expects($this->once())
            ->method('getOwner')
            ->with($this->testEntity)
            ->willReturn($this->testEntity->getOwner());
        $this->entityOwnerAccessor->expects($this->exactly(2))
            ->method('getOrganization')
            ->willReturnMap(
                [
                    [$owner, $owner->getOrganization()],
                    [$this->testEntity, $this->testEntity->getOrganization()],
                ]
            );

        $this->validator->validate($this->testEntity, $this->constraint);
        $this->assertNoViolation();
    }

    public function testInvalidNewEntityWithNewUserOwner()
    {
        $owner = new User();
        $owner->setOrganization(new Organization());

        $this->testEntity->setOwner($owner);
        $this->testEntity->setOrganization($this->currentOrg);

        $this->getOwnershipMetadataExpectation(
            new OwnershipMetadata('USER', 'owner', 'owner', 'organization', 'organization')
        );

        $accessLevel = AccessLevel::DEEP_LEVEL;
        $this->addOneShotIsGrantedObserverExpectation($accessLevel);

        $classMetadata = $this->getClassMetadataMock();
        $this->getClassMetadataExpectation($classMetadata);

        $this->securityFacade->expects($this->once())
            ->method('isGranted')
            ->with('CREATE', 'entity:Oro\Bundle\OrganizationBundle\Tests\Unit\Fixture\Entity\Entity')
            ->willReturn(true);
        $classMetadata->expects($this->once())
            ->method('getIdentifierValues')
            ->with($this->testEntity)
            ->willReturn([]);
        $this->businessUnitManager->expects($this->never())
            ->method('canUserBeSetAsOwner');
        $this->entityOwnerAccessor->expects($this->once())
            ->method('getOwner')
            ->with($this->testEntity)
            ->willReturn($this->testEntity->getOwner());
        $this->entityOwnerAccessor->expects($this->exactly(2))
            ->method('getOrganization')
            ->willReturnMap(
                [
                    [$owner, $owner->getOrganization()],
                    [$this->testEntity, $this->testEntity->getOrganization()],
                ]
            );

        $this->validator->validate($this->testEntity, $this->constraint);
        $this->buildViolation($this->constraint->message)
            ->atPath('owner')
            ->setParameters(['{{ owner }}' => 'owner'])
            ->assertRaised();
    }

    public function testInvalidNewEntityWithNewBusinessUnitOwner()
    {
        $owner = new BusinessUnit();
        $owner->setOrganization(new Organization());

        $this->testEntity->setOwner($owner);
        $this->testEntity->setOrganization($this->currentOrg);

        $this->getOwnershipMetadataExpectation(
            new OwnershipMetadata('BUSINESS_UNIT', 'owner', 'owner', 'organization', 'organization')
        );

        $accessLevel = AccessLevel::DEEP_LEVEL;
        $this->addOneShotIsGrantedObserverExpectation($accessLevel);

        $classMetadata = $this->getClassMetadataMock();
        $this->getClassMetadataExpectation($classMetadata);

        $this->securityFacade->expects($this->once())
            ->method('isGranted')
            ->with('CREATE', 'entity:Oro\Bundle\OrganizationBundle\Tests\Unit\Fixture\Entity\Entity')
            ->willReturn(true);
        $classMetadata->expects($this->once())
            ->method('getIdentifierValues')
            ->with($this->testEntity)
            ->willReturn([]);
        $this->businessUnitManager->expects($this->never())
            ->method('canBusinessUnitBeSetAsOwner');
        $this->entityOwnerAccessor->expects($this->once())
            ->method('getOwner')
            ->with($this->testEntity)
            ->willReturn($this->testEntity->getOwner());
        $this->entityOwnerAccessor->expects($this->exactly(2))
            ->method('getOrganization')
            ->willReturnMap(
                [
                    [$owner, $owner->getOrganization()],
                    [$this->testEntity, $this->testEntity->getOrganization()],
                ]
            );

        $this->validator->validate($this->testEntity, $this->constraint);
        $this->buildViolation($this->constraint->message)
            ->atPath('owner')
            ->setParameters(['{{ owner }}' => 'owner'])
            ->assertRaised();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getObjectManagerMock()
    {
        return $this->getMockBuilder('Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getClassMetadataMock()
    {
        return $this->getMockBuilder('Doctrine\Common\Persistence\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @param \PHPUnit_Framework_MockObject_MockObject $classMetadata
     */
    protected function getClassMetadataExpectation($classMetadata)
    {
        $om = $this->getObjectManagerMock();
        $om->expects($this->once())
            ->method('getClassMetadata')
            ->willReturn($classMetadata);
        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(self::TEST_ENTITY_CLASS)
            ->willReturn($om);
    }

    /**
     * @param OwnershipMetadata $ownershipMetadata
     */
    protected function getOwnershipMetadataExpectation($ownershipMetadata)
    {
        $this->ownershipMetadataProvider->expects($this->once())
            ->method('getMetadata')
            ->with(self::TEST_ENTITY_CLASS)
            ->willReturn($ownershipMetadata);
    }

    /**
     * @param int $accessLevel
     */
    protected function addOneShotIsGrantedObserverExpectation($accessLevel)
    {
        $this->aclVoter->expects($this->once())
            ->method('addOneShotIsGrantedObserver')
            ->will(
                $this->returnCallback(
                    function ($input) use ($accessLevel) {
                        $input->setAccessLevel($accessLevel);
                    }
                )
            );
    }

    protected function getUserOrganizationIdsExpectation(array $organizationIds)
    {
        $ownerTree = $this->getMockBuilder('Oro\Bundle\SecurityBundle\Owner\OwnerTree')
            ->disableOriginalConstructor()
            ->getMock();
        $ownerTree->expects($this->once())
            ->method('getUserOrganizationIds')
            ->willReturn($organizationIds);
        $this->treeProvider->expects($this->once())
            ->method('getTree')
            ->willReturn($ownerTree);
    }
}
