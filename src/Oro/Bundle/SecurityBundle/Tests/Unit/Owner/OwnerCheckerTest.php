<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Owner;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\OrganizationBundle\Entity\Manager\BusinessUnitManager;
use Oro\Bundle\OrganizationBundle\Tests\Unit\Fixture\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Tests\Unit\Fixture\Entity\Entity;
use Oro\Bundle\OrganizationBundle\Tests\Unit\Fixture\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Tests\Unit\Fixture\Entity\User;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Acl\Voter\AclVoter;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SecurityBundle\Owner\EntityOwnerAccessor;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadata;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface;
use Oro\Bundle\SecurityBundle\Owner\OwnerChecker;
use Oro\Bundle\SecurityBundle\Owner\OwnerTreeInterface;
use Oro\Bundle\SecurityBundle\Owner\OwnerTreeProvider;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class OwnerCheckerTest extends \PHPUnit\Framework\TestCase
{
    const TEST_ENTITY_CLASS = 'Oro\Bundle\OrganizationBundle\Tests\Unit\Fixture\Entity\Entity';

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $doctrineHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $ownershipMetadataProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $entityOwnerAccessor;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $businessUnitManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $aclVoter;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $authorizationChecker;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $tokenAccessor;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $treeProvider;

    /** @var Entity */
    protected $testEntity;

    /** @var User */
    protected $currentUser;

    /** @var Organization */
    protected $currentOrg;

    /** @var OwnerChecker */
    protected $ownerChecker;

    protected function setUp()
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->ownershipMetadataProvider = $this->createMock(OwnershipMetadataProviderInterface::class);
        $this->entityOwnerAccessor = $this->createMock(EntityOwnerAccessor::class);
        $this->businessUnitManager = $this->createMock(BusinessUnitManager::class);
        $this->aclVoter = $this->createMock(AclVoter::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->treeProvider = $this->createMock(OwnerTreeProvider::class);

        $this->testEntity = new Entity();
        $this->currentUser = new User();
        $this->currentUser->setId(12);
        $this->currentOrg = new Organization();
        $this->currentOrg->setId(2);

        $this->ownerChecker = new OwnerChecker(
            $this->doctrineHelper,
            $this->businessUnitManager,
            $this->ownershipMetadataProvider,
            $this->entityOwnerAccessor,
            $this->authorizationChecker,
            $this->tokenAccessor,
            $this->treeProvider,
            $this->aclVoter
        );
    }

    public function testValidateOnNonSupportedEntity()
    {
        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntity')
            ->with($this->testEntity)
            ->willReturn(false);

        $this->ownershipMetadataProvider->expects($this->never())
            ->method('getMetadata');

        $this->configureTokenAccessor($this->currentUser, $this->currentOrg);

        $this->assertTrue($this->ownerChecker->isOwnerCanBeSet($this->testEntity));
    }

    public function testValidateOnNonAclProtectedEntity()
    {
        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntity')
            ->with($this->testEntity)
            ->willReturn(true);

        $ownershipMetadata = new OwnershipMetadata();
        $this->getOwnershipMetadataExpectation($ownershipMetadata);

        $this->entityOwnerAccessor->expects($this->never())
            ->method('getOwner');

        $this->configureTokenAccessor($this->currentUser, $this->currentOrg);

        $this->assertTrue($this->ownerChecker->isOwnerCanBeSet($this->testEntity));
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

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('ASSIGN', $this->testEntity)
            ->willReturn(true);
        $this->businessUnitManager->expects($this->once())
            ->method('canUserBeSetAsOwner')
            ->with($this->currentUser, $owner, $accessLevel, $this->treeProvider, $this->currentOrg)
            ->willReturn(true);
        $this->entityOwnerAccessor->expects($this->once())
            ->method('getOwner')
            ->with($this->testEntity)
            ->willReturn($this->testEntity->getOwner());

        $this->configureDoctrineHelper(false);
        $this->configureTokenAccessor($this->currentUser, $this->currentOrg);

        $this->assertTrue($this->ownerChecker->isOwnerCanBeSet($this->testEntity));
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

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('ASSIGN', $this->testEntity)
            ->willReturn(true);
        $this->businessUnitManager->expects($this->once())
            ->method('canBusinessUnitBeSetAsOwner')
            ->with($this->currentUser, $owner, $accessLevel, $this->treeProvider, $this->currentOrg)
            ->willReturn(true);
        $this->entityOwnerAccessor->expects($this->once())
            ->method('getOwner')
            ->with($this->testEntity)
            ->willReturn($this->testEntity->getOwner());

        $this->configureDoctrineHelper(false);
        $this->configureTokenAccessor($this->currentUser, $this->currentOrg);

        $this->assertTrue($this->ownerChecker->isOwnerCanBeSet($this->testEntity));
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

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('ASSIGN', $this->testEntity)
            ->willReturn(true);
        $this->getUserOrganizationIdsExpectation([2, 3, $owner->getId()]);
        $this->entityOwnerAccessor->expects($this->once())
            ->method('getOwner')
            ->with($this->testEntity)
            ->willReturn($this->testEntity->getOwner());

        $this->configureDoctrineHelper(false);
        $this->configureTokenAccessor($this->currentUser, $this->currentOrg);

        $this->assertTrue($this->ownerChecker->isOwnerCanBeSet($this->testEntity));
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

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('ASSIGN', $this->testEntity)
            ->willReturn(true);
        $this->businessUnitManager->expects($this->once())
            ->method('canUserBeSetAsOwner')
            ->with($this->currentUser, $owner, $accessLevel, $this->treeProvider, $this->currentOrg)
            ->willReturn(false);
        $this->entityOwnerAccessor->expects($this->once())
            ->method('getOwner')
            ->with($this->testEntity)
            ->willReturn($this->testEntity->getOwner());

        $this->configureDoctrineHelper(false);
        $this->configureTokenAccessor($this->currentUser, $this->currentOrg);

        $this->assertFalse($this->ownerChecker->isOwnerCanBeSet($this->testEntity));
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

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('ASSIGN', $this->testEntity)
            ->willReturn(true);
        $this->businessUnitManager->expects($this->once())
            ->method('canBusinessUnitBeSetAsOwner')
            ->with($this->currentUser, $owner, $accessLevel, $this->treeProvider, $this->currentOrg)
            ->willReturn(false);
        $this->entityOwnerAccessor->expects($this->once())
            ->method('getOwner')
            ->with($this->testEntity)
            ->willReturn($this->testEntity->getOwner());

        $this->configureDoctrineHelper(false);
        $this->configureTokenAccessor($this->currentUser, $this->currentOrg);

        $this->assertFalse($this->ownerChecker->isOwnerCanBeSet($this->testEntity));
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

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('ASSIGN', $this->testEntity)
            ->willReturn(true);
        $this->getUserOrganizationIdsExpectation([2, 3]);
        $this->entityOwnerAccessor->expects($this->once())
            ->method('getOwner')
            ->with($this->testEntity)
            ->willReturn($this->testEntity->getOwner());

        $this->configureDoctrineHelper(false);
        $this->configureTokenAccessor($this->currentUser, $this->currentOrg);

        $this->assertFalse($this->ownerChecker->isOwnerCanBeSet($this->testEntity));
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

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('CREATE', 'entity:Oro\Bundle\OrganizationBundle\Tests\Unit\Fixture\Entity\Entity')
            ->willReturn(true);
        $this->businessUnitManager->expects($this->once())
            ->method('canUserBeSetAsOwner')
            ->with($this->currentUser, $owner, $accessLevel, $this->treeProvider, $this->currentOrg)
            ->willReturn(true);
        $this->entityOwnerAccessor->expects($this->once())
            ->method('getOwner')
            ->with($this->testEntity)
            ->willReturn($this->testEntity->getOwner());

        $this->configureDoctrineHelper(true);
        $this->configureTokenAccessor($this->currentUser, $this->currentOrg);

        $this->assertTrue($this->ownerChecker->isOwnerCanBeSet($this->testEntity));
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

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('CREATE', 'entity:Oro\Bundle\OrganizationBundle\Tests\Unit\Fixture\Entity\Entity')
            ->willReturn(true);
        $this->businessUnitManager->expects($this->once())
            ->method('canBusinessUnitBeSetAsOwner')
            ->with($this->currentUser, $owner, $accessLevel, $this->treeProvider, $this->currentOrg)
            ->willReturn(true);
        $this->entityOwnerAccessor->expects($this->once())
            ->method('getOwner')
            ->with($this->testEntity)
            ->willReturn($this->testEntity->getOwner());

        $this->configureDoctrineHelper(true);
        $this->configureTokenAccessor($this->currentUser, $this->currentOrg);

        $this->assertTrue($this->ownerChecker->isOwnerCanBeSet($this->testEntity));
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

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('CREATE', 'entity:Oro\Bundle\OrganizationBundle\Tests\Unit\Fixture\Entity\Entity')
            ->willReturn(true);
        $this->getUserOrganizationIdsExpectation([2, 3, $owner->getId()]);
        $this->entityOwnerAccessor->expects($this->once())
            ->method('getOwner')
            ->with($this->testEntity)
            ->willReturn($this->testEntity->getOwner());

        $this->configureDoctrineHelper(true);
        $this->configureTokenAccessor($this->currentUser, $this->currentOrg);

        $this->assertTrue($this->ownerChecker->isOwnerCanBeSet($this->testEntity));
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

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('CREATE', 'entity:Oro\Bundle\OrganizationBundle\Tests\Unit\Fixture\Entity\Entity')
            ->willReturn(true);
        $this->businessUnitManager->expects($this->once())
            ->method('canUserBeSetAsOwner')
            ->with($this->currentUser, $owner, $accessLevel, $this->treeProvider, $this->currentOrg)
            ->willReturn(false);
        $this->entityOwnerAccessor->expects($this->once())
            ->method('getOwner')
            ->with($this->testEntity)
            ->willReturn($this->testEntity->getOwner());

        $this->configureDoctrineHelper(true);
        $this->configureTokenAccessor($this->currentUser, $this->currentOrg);

        $this->assertFalse($this->ownerChecker->isOwnerCanBeSet($this->testEntity));
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

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('CREATE', 'entity:Oro\Bundle\OrganizationBundle\Tests\Unit\Fixture\Entity\Entity')
            ->willReturn(true);
        $this->businessUnitManager->expects($this->once())
            ->method('canBusinessUnitBeSetAsOwner')
            ->with($this->currentUser, $owner, $accessLevel, $this->treeProvider, $this->currentOrg)
            ->willReturn(false);
        $this->entityOwnerAccessor->expects($this->once())
            ->method('getOwner')
            ->with($this->testEntity)
            ->willReturn($this->testEntity->getOwner());

        $this->configureDoctrineHelper(true);
        $this->configureTokenAccessor($this->currentUser, $this->currentOrg);

        $this->assertFalse($this->ownerChecker->isOwnerCanBeSet($this->testEntity));
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

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('CREATE', 'entity:Oro\Bundle\OrganizationBundle\Tests\Unit\Fixture\Entity\Entity')
            ->willReturn(true);
        $this->getUserOrganizationIdsExpectation([2, 3]);
        $this->entityOwnerAccessor->expects($this->once())
            ->method('getOwner')
            ->with($this->testEntity)
            ->willReturn($this->testEntity->getOwner());

        $this->configureDoctrineHelper(true);
        $this->configureTokenAccessor($this->currentUser, $this->currentOrg);

        $this->assertFalse($this->ownerChecker->isOwnerCanBeSet($this->testEntity));
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

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('CREATE', 'entity:Oro\Bundle\OrganizationBundle\Tests\Unit\Fixture\Entity\Entity')
            ->willReturn(true);
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

        $this->configureDoctrineHelper(true);
        $this->configureTokenAccessor($this->currentUser, $this->currentOrg);

        $this->assertTrue($this->ownerChecker->isOwnerCanBeSet($this->testEntity));
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

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('CREATE', 'entity:Oro\Bundle\OrganizationBundle\Tests\Unit\Fixture\Entity\Entity')
            ->willReturn(true);
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

        $this->configureDoctrineHelper(true);
        $this->configureTokenAccessor($this->currentUser, $this->currentOrg);

        $this->assertTrue($this->ownerChecker->isOwnerCanBeSet($this->testEntity));
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

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('CREATE', 'entity:Oro\Bundle\OrganizationBundle\Tests\Unit\Fixture\Entity\Entity')
            ->willReturn(true);
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

        $this->configureDoctrineHelper(true);
        $this->configureTokenAccessor($this->currentUser, $this->currentOrg);

        $this->assertFalse($this->ownerChecker->isOwnerCanBeSet($this->testEntity));
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

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('CREATE', 'entity:Oro\Bundle\OrganizationBundle\Tests\Unit\Fixture\Entity\Entity')
            ->willReturn(true);
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

        $this->configureDoctrineHelper(true);
        $this->configureTokenAccessor($this->currentUser, $this->currentOrg);

        $this->assertFalse($this->ownerChecker->isOwnerCanBeSet($this->testEntity));
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
        $ownerTree = $this->getMockBuilder(OwnerTreeInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $ownerTree->expects($this->once())
            ->method('getUserOrganizationIds')
            ->willReturn($organizationIds);
        $this->treeProvider->expects($this->once())
            ->method('getTree')
            ->willReturn($ownerTree);
    }

    /**
     * @param User $user
     * @param Organization $organization
     */
    private function configureTokenAccessor(User $user, Organization $organization): void
    {
        $this->tokenAccessor->expects($this->any())
            ->method('getUser')
            ->willReturn($user);

        $this->tokenAccessor->expects($this->any())
            ->method('hasUser')
            ->willReturn(true);

        $this->tokenAccessor->expects($this->any())
            ->method('getUserId')
            ->willReturn($user->getId());

        $this->tokenAccessor->expects($this->any())
            ->method('getOrganization')
            ->willReturn($organization);
    }

    /**
     * @param bool $isEntityNew
     */
    private function configureDoctrineHelper(bool $isEntityNew): void
    {
        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntity')
            ->with($this->testEntity)
            ->willReturn(true);

        $this->doctrineHelper->expects($this->once())
            ->method('isNewEntity')
            ->with($this->testEntity)
            ->willReturn($isEntityNew);
    }
}
