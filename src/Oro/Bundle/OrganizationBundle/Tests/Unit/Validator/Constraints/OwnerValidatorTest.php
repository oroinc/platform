<?php

namespace Oro\Bundle\OrganizationBundle\Tests\Unit\Validator\Constraints;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\OrganizationBundle\Entity\Manager\BusinessUnitManager;
use Oro\Bundle\OrganizationBundle\Tests\Unit\Fixture\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Tests\Unit\Fixture\Entity\Entity;
use Oro\Bundle\OrganizationBundle\Tests\Unit\Fixture\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Tests\Unit\Fixture\Entity\User;
use Oro\Bundle\OrganizationBundle\Validator\Constraints\Owner;
use Oro\Bundle\OrganizationBundle\Validator\Constraints\OwnerValidator;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Acl\Domain\OneShotIsGrantedObserver;
use Oro\Bundle\SecurityBundle\Acl\Group\AclGroupProviderInterface;
use Oro\Bundle\SecurityBundle\Acl\Voter\AclVoter;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadata;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface;
use Oro\Bundle\SecurityBundle\Owner\OwnerTreeInterface;
use Oro\Bundle\SecurityBundle\Owner\OwnerTreeProviderInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class OwnerValidatorTest extends ConstraintValidatorTestCase
{
    private \PHPUnit\Framework\MockObject\MockObject|ManagerRegistry $doctrine;

    private \PHPUnit\Framework\MockObject\MockObject|OwnershipMetadataProviderInterface $ownershipMetadataProvider;

    private \PHPUnit\Framework\MockObject\MockObject|AuthorizationCheckerInterface $authorizationChecker;

    private \PHPUnit\Framework\MockObject\MockObject|TokenAccessorInterface $tokenAccessor;

    private \PHPUnit\Framework\MockObject\MockObject|OwnerTreeProviderInterface $ownerTreeProvider;

    private \PHPUnit\Framework\MockObject\MockObject|AclVoter $aclVoter;

    private \PHPUnit\Framework\MockObject\MockObject|AclGroupProviderInterface $aclGroupProvider;

    private \PHPUnit\Framework\MockObject\MockObject|BusinessUnitManager $businessUnitManager;

    private Entity $testEntity;

    private User $currentUser;

    private Organization $currentOrg;

    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->ownershipMetadataProvider = $this->createMock(OwnershipMetadataProviderInterface::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->ownerTreeProvider = $this->createMock(OwnerTreeProviderInterface::class);
        $this->aclVoter = $this->createMock(AclVoter::class);
        $this->aclGroupProvider = $this->createMock(AclGroupProviderInterface::class);
        $this->businessUnitManager = $this->createMock(BusinessUnitManager::class);

        $this->testEntity = new Entity();
        $this->currentOrg = new Organization();
        $this->currentOrg->setId(1);
        $this->currentUser = new User();
        $this->currentUser->setId(10);

        $this->tokenAccessor->expects(self::any())
            ->method('getUser')
            ->willReturn($this->currentUser);
        $this->tokenAccessor->expects(self::any())
            ->method('getUserId')
            ->willReturn($this->currentUser->getId());
        $this->tokenAccessor->expects(self::any())
            ->method('getOrganization')
            ->willReturn($this->currentOrg);

        parent::setUp();
    }

    /**
     * {@inheritDoc}
     */
    protected function createValidator()
    {
        return new OwnerValidator(
            $this->doctrine,
            $this->ownershipMetadataProvider,
            $this->authorizationChecker,
            $this->tokenAccessor,
            $this->ownerTreeProvider,
            $this->aclVoter,
            $this->aclGroupProvider,
            $this->businessUnitManager
        );
    }

    /**
     * @param string $ownerType
     *
     * @return OwnershipMetadata
     */
    private function createOwnershipMetadata($ownerType)
    {
        return new OwnershipMetadata($ownerType, 'owner', 'owner', 'organization', 'organization');
    }

    /**
     * {@inheritDoc}
     */
    protected function createContext()
    {
        $this->constraint = new Owner();
        $this->propertyPath = '';

        return parent::createContext();
    }

    /**
     * @param int $id
     *
     * @return User
     */
    private function createUser($id): User
    {
        $user = new User();
        $user->setId($id);

        return $user;
    }

    /**
     * @param int $id
     *
     * @return BusinessUnit
     */
    private function createBusinessUnit($id): BusinessUnit
    {
        $businessUnit = new BusinessUnit();
        $businessUnit->setId($id);

        return $businessUnit;
    }

    /**
     * @param int $id
     *
     * @return Organization
     */
    private function createOrganization($id): Organization
    {
        $organization = new Organization();
        $organization->setId($id);

        return $organization;
    }

    /**
     * @param \PHPUnit\Framework\MockObject\MockObject|ClassMetadata $entityMetadata
     * @param array                                                  $originalEntityData
     *
     * @return \PHPUnit\Framework\MockObject\MockObject|UnitOfWork
     */
    private function expectManageableEntity(ClassMetadata $entityMetadata, array $originalEntityData)
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $uow = $this->createMock(UnitOfWork::class);

        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(Entity::class)
            ->willReturn($em);
        $em->expects(self::once())
            ->method('getClassMetadata')
            ->willReturn($entityMetadata);
        $em->expects(self::any())
            ->method('getUnitOfWork')
            ->willReturn($uow);

        $uow->expects(self::any())
            ->method('getOriginalEntityData')
            ->with($this->testEntity)
            ->willReturn($originalEntityData);

        return $uow;
    }

    /**
     * @param int $accessLevel
     */
    private function expectAddOneShotIsGrantedObserver($accessLevel): void
    {
        $this->aclVoter->expects(self::once())
            ->method('addOneShotIsGrantedObserver')
            ->willReturnCallback(function (OneShotIsGrantedObserver $observer) use ($accessLevel) {
                $observer->setAccessLevel($accessLevel);
            });
    }

    private function expectGetUserOrganizationIds(array $organizationIds): void
    {
        $ownerTree = $this->createMock(OwnerTreeInterface::class);
        $ownerTree->expects(self::once())
            ->method('getUserOrganizationIds')
            ->willReturn($organizationIds);
        $this->ownerTreeProvider->expects(self::once())
            ->method('getTree')
            ->willReturn($ownerTree);
    }

    public function testValidateForInvalidConstraintType(): void
    {
        $this->expectException(\Symfony\Component\Validator\Exception\UnexpectedTypeException::class);
        $this->validator->validate($this->testEntity, $this->createMock(Constraint::class));
    }

    public function testValidateForNull(): void
    {
        $this->doctrine->expects(self::never())
            ->method('getManagerForClass');

        $this->validator->validate(null, $this->constraint);
        $this->assertNoViolation();
    }

    public function testValidateForNotManageableEntity(): void
    {
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(Entity::class)
            ->willReturn(null);
        $this->ownershipMetadataProvider->expects(self::never())
            ->method('getMetadata');

        $this->validator->validate($this->testEntity, $this->constraint);
        $this->assertNoViolation();
    }

    public function testValidateForNonAclProtectedEntity(): void
    {
        $ownershipMetadata = new OwnershipMetadata();

        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(Entity::class)
            ->willReturn($this->createMock(EntityManagerInterface::class));
        $this->ownershipMetadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with(Entity::class)
            ->willReturn($ownershipMetadata);

        $this->validator->validate($this->testEntity, $this->constraint);
        $this->assertNoViolation();
    }

    public function testValidWithNullOwner(): void
    {
        $ownershipMetadata = $this->createOwnershipMetadata('USER');
        $entityMetadata = $this->createMock(ClassMetadata::class);

        $owner = null;
        $this->testEntity->setId(234);
        $this->testEntity->setOwner($owner);

        $this->expectManageableEntity($entityMetadata, []);
        $entityMetadata->expects(self::once())
            ->method('getFieldValue')
            ->with($this->testEntity, $ownershipMetadata->getOwnerFieldName())
            ->willReturn($owner);
        $entityMetadata->expects(self::never())
            ->method('getIdentifierValues');

        $this->ownershipMetadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with(Entity::class)
            ->willReturn($ownershipMetadata);

        $this->aclVoter->expects(self::never())
            ->method('addOneShotIsGrantedObserver');
        $this->authorizationChecker->expects(self::never())
            ->method('isGranted');
        $this->businessUnitManager->expects(self::never())
            ->method('canUserBeSetAsOwner');

        $this->validator->validate($this->testEntity, $this->constraint);
        $this->assertNoViolation();
    }

    public function testValidWithNotChangedOwner(): void
    {
        $ownershipMetadata = $this->createOwnershipMetadata('USER');
        $entityMetadata = $this->createMock(ClassMetadata::class);

        $owner = $this->createUser(123);
        $this->testEntity->setId(234);
        $this->testEntity->setOwner($owner);

        $this->expectManageableEntity($entityMetadata, [$ownershipMetadata->getOwnerFieldName() => $owner]);
        $entityMetadata->expects(self::once())
            ->method('getFieldValue')
            ->with($this->testEntity, $ownershipMetadata->getOwnerFieldName())
            ->willReturn($owner);
        $entityMetadata->expects(self::never())
            ->method('getIdentifierValues');

        $this->ownershipMetadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with(Entity::class)
            ->willReturn($ownershipMetadata);

        $this->aclVoter->expects(self::never())
            ->method('addOneShotIsGrantedObserver');
        $this->authorizationChecker->expects(self::never())
            ->method('isGranted');
        $this->businessUnitManager->expects(self::never())
            ->method('canUserBeSetAsOwner');

        $this->validator->validate($this->testEntity, $this->constraint);
        $this->assertNoViolation();
    }

    public function testInvalidBecauseAccessDenied(): void
    {
        $ownershipMetadata = $this->createOwnershipMetadata('USER');
        $entityMetadata = $this->createMock(ClassMetadata::class);
        $accessLevel = null;

        $owner = $this->createUser(123);
        $this->testEntity->setId(234);
        $this->testEntity->setOwner($owner);

        $this->expectManageableEntity($entityMetadata, [$ownershipMetadata->getOwnerFieldName() => null]);
        $entityMetadata->expects(self::once())
            ->method('getFieldValue')
            ->with($this->testEntity, $ownershipMetadata->getOwnerFieldName())
            ->willReturn($owner);
        $entityMetadata->expects(self::once())
            ->method('getIdentifierValues')
            ->with($this->testEntity)
            ->willReturn([$this->testEntity->getId()]);

        $this->ownershipMetadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with(Entity::class)
            ->willReturn($ownershipMetadata);

        $this->expectAddOneShotIsGrantedObserver($accessLevel);
        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('ASSIGN', 'entity:' . Entity::class)
            ->willReturn(true);
        $this->businessUnitManager->expects(self::never())
            ->method('canUserBeSetAsOwner');

        $this->validator->validate($this->testEntity, $this->constraint);
        $this->buildViolation($this->constraint->message)
            ->atPath('owner')
            ->setParameters(['{{ owner }}' => 'owner'])
            ->assertRaised();
    }

    public function testValidExistingEntityWithUserOwner(): void
    {
        $ownershipMetadata = $this->createOwnershipMetadata('USER');
        $entityMetadata = $this->createMock(ClassMetadata::class);
        $accessLevel = AccessLevel::DEEP_LEVEL;

        $owner = $this->createUser(123);
        $this->testEntity->setId(234);
        $this->testEntity->setOwner($owner);

        $this->expectManageableEntity($entityMetadata, [$ownershipMetadata->getOwnerFieldName() => null]);
        $entityMetadata->expects(self::once())
            ->method('getFieldValue')
            ->with($this->testEntity, $ownershipMetadata->getOwnerFieldName())
            ->willReturn($owner);
        $entityMetadata->expects(self::once())
            ->method('getIdentifierValues')
            ->with($this->testEntity)
            ->willReturn([$this->testEntity->getId()]);

        $this->ownershipMetadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with(Entity::class)
            ->willReturn($ownershipMetadata);

        $this->expectAddOneShotIsGrantedObserver($accessLevel);
        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('ASSIGN', 'entity:' . Entity::class)
            ->willReturn(true);
        $this->businessUnitManager->expects(self::once())
            ->method('canUserBeSetAsOwner')
            ->with($this->currentUser, $owner, $accessLevel, $this->ownerTreeProvider, $this->currentOrg)
            ->willReturn(true);

        $this->validator->validate($this->testEntity, $this->constraint);
        $this->assertNoViolation();
    }

    public function testValidExistingEntityWithBusinessUnitOwner(): void
    {
        $ownershipMetadata = $this->createOwnershipMetadata('BUSINESS_UNIT');
        $entityMetadata = $this->createMock(ClassMetadata::class);
        $accessLevel = AccessLevel::DEEP_LEVEL;

        $owner = $this->createBusinessUnit(123);
        $this->testEntity->setId(234);
        $this->testEntity->setOwner($owner);

        $this->expectManageableEntity($entityMetadata, [$ownershipMetadata->getOwnerFieldName() => null]);
        $entityMetadata->expects(self::once())
            ->method('getFieldValue')
            ->with($this->testEntity, $ownershipMetadata->getOwnerFieldName())
            ->willReturn($owner);
        $entityMetadata->expects(self::once())
            ->method('getIdentifierValues')
            ->with($this->testEntity)
            ->willReturn([$this->testEntity->getId()]);

        $this->ownershipMetadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with(Entity::class)
            ->willReturn($ownershipMetadata);

        $this->expectAddOneShotIsGrantedObserver($accessLevel);
        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('ASSIGN', 'entity:' . Entity::class)
            ->willReturn(true);
        $this->businessUnitManager->expects(self::once())
            ->method('canBusinessUnitBeSetAsOwner')
            ->with($this->currentUser, $owner, $accessLevel, $this->ownerTreeProvider, $this->currentOrg)
            ->willReturn(true);

        $this->validator->validate($this->testEntity, $this->constraint);
        $this->assertNoViolation();
    }

    public function testValidExistingEntityWithOrganizationOwner(): void
    {
        $ownershipMetadata = $this->createOwnershipMetadata('ORGANIZATION');
        $entityMetadata = $this->createMock(ClassMetadata::class);
        $accessLevel = AccessLevel::DEEP_LEVEL;

        $owner = $this->createOrganization(123);
        $this->testEntity->setId(234);
        $this->testEntity->setOwner($owner);

        $this->expectManageableEntity($entityMetadata, [$ownershipMetadata->getOwnerFieldName() => null]);
        $entityMetadata->expects(self::once())
            ->method('getFieldValue')
            ->with($this->testEntity, $ownershipMetadata->getOwnerFieldName())
            ->willReturn($owner);
        $entityMetadata->expects(self::once())
            ->method('getIdentifierValues')
            ->with($this->testEntity)
            ->willReturn([$this->testEntity->getId()]);

        $this->ownershipMetadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with(Entity::class)
            ->willReturn($ownershipMetadata);

        $this->expectAddOneShotIsGrantedObserver($accessLevel);
        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('ASSIGN', 'entity:' . Entity::class)
            ->willReturn(true);
        $this->expectGetUserOrganizationIds([2, 3, $owner->getId()]);

        $this->validator->validate($this->testEntity, $this->constraint);
        $this->assertNoViolation();
    }

    public function testInvalidExistingEntityWithUserOwner(): void
    {
        $ownershipMetadata = $this->createOwnershipMetadata('USER');
        $entityMetadata = $this->createMock(ClassMetadata::class);
        $accessLevel = AccessLevel::DEEP_LEVEL;

        $owner = $this->createUser(123);
        $this->testEntity->setId(234);
        $this->testEntity->setOwner($owner);

        $this->expectManageableEntity($entityMetadata, [$ownershipMetadata->getOwnerFieldName() => null]);
        $entityMetadata->expects(self::once())
            ->method('getFieldValue')
            ->with($this->testEntity, $ownershipMetadata->getOwnerFieldName())
            ->willReturn($owner);
        $entityMetadata->expects(self::once())
            ->method('getIdentifierValues')
            ->with($this->testEntity)
            ->willReturn([$this->testEntity->getId()]);

        $this->ownershipMetadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with(Entity::class)
            ->willReturn($ownershipMetadata);

        $this->expectAddOneShotIsGrantedObserver($accessLevel);
        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('ASSIGN', 'entity:' . Entity::class)
            ->willReturn(true);
        $this->businessUnitManager->expects(self::once())
            ->method('canUserBeSetAsOwner')
            ->with($this->currentUser, $owner, $accessLevel, $this->ownerTreeProvider, $this->currentOrg)
            ->willReturn(false);

        $this->validator->validate($this->testEntity, $this->constraint);
        $this->buildViolation($this->constraint->message)
            ->atPath('owner')
            ->setParameters(['{{ owner }}' => 'owner'])
            ->assertRaised();
    }

    public function testInvalidExistingEntityWithBusinessUnitOwner(): void
    {
        $ownershipMetadata = $this->createOwnershipMetadata('BUSINESS_UNIT');
        $entityMetadata = $this->createMock(ClassMetadata::class);
        $accessLevel = AccessLevel::DEEP_LEVEL;

        $owner = $this->createBusinessUnit(123);
        $this->testEntity->setId(234);
        $this->testEntity->setOwner($owner);

        $this->expectManageableEntity($entityMetadata, [$ownershipMetadata->getOwnerFieldName() => null]);
        $entityMetadata->expects(self::once())
            ->method('getFieldValue')
            ->with($this->testEntity, $ownershipMetadata->getOwnerFieldName())
            ->willReturn($owner);
        $entityMetadata->expects(self::once())
            ->method('getIdentifierValues')
            ->with($this->testEntity)
            ->willReturn([$this->testEntity->getId()]);

        $this->ownershipMetadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with(Entity::class)
            ->willReturn($ownershipMetadata);

        $this->expectAddOneShotIsGrantedObserver($accessLevel);
        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('ASSIGN', 'entity:' . Entity::class)
            ->willReturn(true);
        $this->businessUnitManager->expects(self::once())
            ->method('canBusinessUnitBeSetAsOwner')
            ->with($this->currentUser, $owner, $accessLevel, $this->ownerTreeProvider, $this->currentOrg)
            ->willReturn(false);

        $this->validator->validate($this->testEntity, $this->constraint);
        $this->buildViolation($this->constraint->message)
            ->atPath('owner')
            ->setParameters(['{{ owner }}' => 'owner'])
            ->assertRaised();
    }

    public function testInvalidExistingEntityWithOrganizationOwner(): void
    {
        $ownershipMetadata = $this->createOwnershipMetadata('ORGANIZATION');
        $entityMetadata = $this->createMock(ClassMetadata::class);
        $accessLevel = AccessLevel::DEEP_LEVEL;

        $owner = $this->createOrganization(123);
        $this->testEntity->setId(234);
        $this->testEntity->setOwner($owner);

        $this->expectManageableEntity($entityMetadata, [$ownershipMetadata->getOwnerFieldName() => null]);
        $entityMetadata->expects(self::once())
            ->method('getFieldValue')
            ->with($this->testEntity, $ownershipMetadata->getOwnerFieldName())
            ->willReturn($owner);
        $entityMetadata->expects(self::once())
            ->method('getIdentifierValues')
            ->with($this->testEntity)
            ->willReturn([$this->testEntity->getId()]);

        $this->ownershipMetadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with(Entity::class)
            ->willReturn($ownershipMetadata);

        $this->expectAddOneShotIsGrantedObserver($accessLevel);
        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('ASSIGN', 'entity:' . Entity::class)
            ->willReturn(true);
        $this->expectGetUserOrganizationIds([2, 3]);

        $this->validator->validate($this->testEntity, $this->constraint);
        $this->buildViolation($this->constraint->message)
            ->atPath('owner')
            ->setParameters(['{{ owner }}' => 'owner'])
            ->assertRaised();
    }

    public function testValidNewEntityWithUserOwner(): void
    {
        $ownershipMetadata = $this->createOwnershipMetadata('USER');
        $entityMetadata = $this->createMock(ClassMetadata::class);
        $accessLevel = AccessLevel::DEEP_LEVEL;

        $owner = $this->createUser(123);
        $this->testEntity->setOwner($owner);

        $this->expectManageableEntity($entityMetadata, [$ownershipMetadata->getOwnerFieldName() => null]);
        $entityMetadata->expects(self::once())
            ->method('getFieldValue')
            ->with($this->testEntity, $ownershipMetadata->getOwnerFieldName())
            ->willReturn($owner);
        $entityMetadata->expects(self::once())
            ->method('getIdentifierValues')
            ->with($this->testEntity)
            ->willReturn([]);

        $this->ownershipMetadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with(Entity::class)
            ->willReturn($ownershipMetadata);

        $this->expectAddOneShotIsGrantedObserver($accessLevel);
        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('CREATE', 'entity:' . Entity::class)
            ->willReturn(true);
        $this->businessUnitManager->expects(self::once())
            ->method('canUserBeSetAsOwner')
            ->with($this->currentUser, $owner, $accessLevel, $this->ownerTreeProvider, $this->currentOrg)
            ->willReturn(true);

        $this->validator->validate($this->testEntity, $this->constraint);
        $this->assertNoViolation();
    }

    public function testValidNewEntityWithBusinessUnitOwner(): void
    {
        $ownershipMetadata = $this->createOwnershipMetadata('BUSINESS_UNIT');
        $entityMetadata = $this->createMock(ClassMetadata::class);
        $accessLevel = AccessLevel::DEEP_LEVEL;

        $owner = $this->createBusinessUnit(123);
        $this->testEntity->setOwner($owner);

        $this->expectManageableEntity($entityMetadata, [$ownershipMetadata->getOwnerFieldName() => null]);
        $entityMetadata->expects(self::once())
            ->method('getFieldValue')
            ->with($this->testEntity, $ownershipMetadata->getOwnerFieldName())
            ->willReturn($owner);
        $entityMetadata->expects(self::once())
            ->method('getIdentifierValues')
            ->with($this->testEntity)
            ->willReturn([]);

        $this->ownershipMetadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with(Entity::class)
            ->willReturn($ownershipMetadata);

        $this->expectAddOneShotIsGrantedObserver($accessLevel);
        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('CREATE', 'entity:' . Entity::class)
            ->willReturn(true);
        $this->businessUnitManager->expects(self::once())
            ->method('canBusinessUnitBeSetAsOwner')
            ->with($this->currentUser, $owner, $accessLevel, $this->ownerTreeProvider, $this->currentOrg)
            ->willReturn(true);

        $this->validator->validate($this->testEntity, $this->constraint);
        $this->assertNoViolation();
    }

    public function testValidNewEntityWithOrganizationOwner(): void
    {
        $ownershipMetadata = $this->createOwnershipMetadata('ORGANIZATION');
        $entityMetadata = $this->createMock(ClassMetadata::class);
        $accessLevel = AccessLevel::DEEP_LEVEL;

        $owner = $this->createOrganization(123);
        $this->testEntity->setOwner($owner);

        $this->expectManageableEntity($entityMetadata, [$ownershipMetadata->getOwnerFieldName() => null]);
        $entityMetadata->expects(self::once())
            ->method('getFieldValue')
            ->with($this->testEntity, $ownershipMetadata->getOwnerFieldName())
            ->willReturn($owner);
        $entityMetadata->expects(self::once())
            ->method('getIdentifierValues')
            ->with($this->testEntity)
            ->willReturn([]);

        $this->ownershipMetadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with(Entity::class)
            ->willReturn($ownershipMetadata);

        $this->expectAddOneShotIsGrantedObserver($accessLevel);
        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('CREATE', 'entity:' . Entity::class)
            ->willReturn(true);
        $this->expectGetUserOrganizationIds([2, 3, $owner->getId()]);

        $this->validator->validate($this->testEntity, $this->constraint);
        $this->assertNoViolation();
    }

    public function testInvalidNewEntityWithUserOwner(): void
    {
        $ownershipMetadata = $this->createOwnershipMetadata('USER');
        $entityMetadata = $this->createMock(ClassMetadata::class);
        $accessLevel = AccessLevel::DEEP_LEVEL;

        $owner = $this->createUser(123);
        $this->testEntity->setOwner($owner);

        $this->expectManageableEntity($entityMetadata, [$ownershipMetadata->getOwnerFieldName() => null]);
        $entityMetadata->expects(self::once())
            ->method('getFieldValue')
            ->with($this->testEntity, $ownershipMetadata->getOwnerFieldName())
            ->willReturn($owner);
        $entityMetadata->expects(self::once())
            ->method('getIdentifierValues')
            ->with($this->testEntity)
            ->willReturn([]);

        $this->ownershipMetadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with(Entity::class)
            ->willReturn($ownershipMetadata);

        $this->expectAddOneShotIsGrantedObserver($accessLevel);
        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('CREATE', 'entity:' . Entity::class)
            ->willReturn(true);
        $this->businessUnitManager->expects(self::once())
            ->method('canUserBeSetAsOwner')
            ->with($this->currentUser, $owner, $accessLevel, $this->ownerTreeProvider, $this->currentOrg)
            ->willReturn(false);

        $this->validator->validate($this->testEntity, $this->constraint);
        $this->buildViolation($this->constraint->message)
            ->atPath('owner')
            ->setParameters(['{{ owner }}' => 'owner'])
            ->assertRaised();
    }

    public function testInvalidNewEntityWithBusinessUnitOwner(): void
    {
        $ownershipMetadata = $this->createOwnershipMetadata('BUSINESS_UNIT');
        $entityMetadata = $this->createMock(ClassMetadata::class);
        $accessLevel = AccessLevel::DEEP_LEVEL;

        $owner = $this->createBusinessUnit(123);
        $this->testEntity->setOwner($owner);

        $this->expectManageableEntity($entityMetadata, [$ownershipMetadata->getOwnerFieldName() => null]);
        $entityMetadata->expects(self::once())
            ->method('getFieldValue')
            ->with($this->testEntity, $ownershipMetadata->getOwnerFieldName())
            ->willReturn($owner);
        $entityMetadata->expects(self::once())
            ->method('getIdentifierValues')
            ->with($this->testEntity)
            ->willReturn([]);

        $this->ownershipMetadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with(Entity::class)
            ->willReturn($ownershipMetadata);

        $this->expectAddOneShotIsGrantedObserver($accessLevel);
        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('CREATE', 'entity:' . Entity::class)
            ->willReturn(true);
        $this->businessUnitManager->expects(self::once())
            ->method('canBusinessUnitBeSetAsOwner')
            ->with($this->currentUser, $owner, $accessLevel, $this->ownerTreeProvider, $this->currentOrg)
            ->willReturn(false);

        $this->validator->validate($this->testEntity, $this->constraint);
        $this->buildViolation($this->constraint->message)
            ->atPath('owner')
            ->setParameters(['{{ owner }}' => 'owner'])
            ->assertRaised();
    }

    public function testInvalidNewEntityWithOrganizationOwner(): void
    {
        $ownershipMetadata = $this->createOwnershipMetadata('ORGANIZATION');
        $entityMetadata = $this->createMock(ClassMetadata::class);
        $accessLevel = AccessLevel::DEEP_LEVEL;

        $owner = $this->createOrganization(123);
        $this->testEntity->setOwner($owner);

        $this->expectManageableEntity($entityMetadata, [$ownershipMetadata->getOwnerFieldName() => null]);
        $entityMetadata->expects(self::once())
            ->method('getFieldValue')
            ->with($this->testEntity, $ownershipMetadata->getOwnerFieldName())
            ->willReturn($owner);
        $entityMetadata->expects(self::once())
            ->method('getIdentifierValues')
            ->with($this->testEntity)
            ->willReturn([]);

        $this->ownershipMetadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with(Entity::class)
            ->willReturn($ownershipMetadata);

        $this->expectAddOneShotIsGrantedObserver($accessLevel);
        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('CREATE', 'entity:' . Entity::class)
            ->willReturn(true);
        $this->expectGetUserOrganizationIds([2, 3]);

        $this->validator->validate($this->testEntity, $this->constraint);
        $this->buildViolation($this->constraint->message)
            ->atPath('owner')
            ->setParameters(['{{ owner }}' => 'owner'])
            ->assertRaised();
    }

    public function testValidNewEntityWithNewUserOwner(): void
    {
        $ownershipMetadata = $this->createOwnershipMetadata('USER');
        $entityMetadata = $this->createMock(ClassMetadata::class);
        $accessLevel = AccessLevel::DEEP_LEVEL;

        $owner = new User();
        $owner->setOrganization($this->currentOrg);
        $this->testEntity->setOwner($owner);
        $this->testEntity->setOrganization($this->currentOrg);

        $this->expectManageableEntity($entityMetadata, [$ownershipMetadata->getOwnerFieldName() => null]);
        $entityMetadata->expects(self::exactly(2))
            ->method('getFieldValue')
            ->willReturnMap([
                [$this->testEntity, $ownershipMetadata->getOwnerFieldName(), $owner],
                [
                    $this->testEntity,
                    $ownershipMetadata->getOrganizationFieldName(),
                    $this->testEntity->getOrganization()
                ]
            ]);
        $entityMetadata->expects(self::once())
            ->method('getIdentifierValues')
            ->with($this->testEntity)
            ->willReturn([]);

        $this->ownershipMetadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with(Entity::class)
            ->willReturn($ownershipMetadata);

        $this->expectAddOneShotIsGrantedObserver($accessLevel);
        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('CREATE', 'entity:' . Entity::class)
            ->willReturn(true);
        $this->businessUnitManager->expects(self::never())
            ->method('canUserBeSetAsOwner');

        $this->validator->validate($this->testEntity, $this->constraint);
        $this->assertNoViolation();
    }

    public function testValidNewEntityWithNewBusinessUnitOwner(): void
    {
        $ownershipMetadata = $this->createOwnershipMetadata('BUSINESS_UNIT');
        $entityMetadata = $this->createMock(ClassMetadata::class);
        $accessLevel = AccessLevel::DEEP_LEVEL;

        $owner = new BusinessUnit();
        $owner->setOrganization($this->currentOrg);
        $this->testEntity->setOwner($owner);
        $this->testEntity->setOrganization($this->currentOrg);

        $this->expectManageableEntity($entityMetadata, [$ownershipMetadata->getOwnerFieldName() => null]);
        $entityMetadata->expects(self::exactly(2))
            ->method('getFieldValue')
            ->willReturnMap([
                [$this->testEntity, $ownershipMetadata->getOwnerFieldName(), $owner],
                [
                    $this->testEntity,
                    $ownershipMetadata->getOrganizationFieldName(),
                    $this->testEntity->getOrganization()
                ]
            ]);
        $entityMetadata->expects(self::once())
            ->method('getIdentifierValues')
            ->with($this->testEntity)
            ->willReturn([]);

        $this->ownershipMetadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with(Entity::class)
            ->willReturn($ownershipMetadata);

        $this->expectAddOneShotIsGrantedObserver($accessLevel);
        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('CREATE', 'entity:' . Entity::class)
            ->willReturn(true);
        $this->businessUnitManager->expects(self::never())
            ->method('canBusinessUnitBeSetAsOwner');

        $this->validator->validate($this->testEntity, $this->constraint);
        $this->assertNoViolation();
    }

    public function testValidNewEntityWithNewOrganizationOwner(): void
    {
        $ownershipMetadata = $this->createOwnershipMetadata('ORGANIZATION');
        $entityMetadata = $this->createMock(ClassMetadata::class);
        $accessLevel = AccessLevel::DEEP_LEVEL;

        $owner = new Organization();
        $this->testEntity->setOwner($owner);
        $this->testEntity->setOrganization($owner);

        $this->expectManageableEntity($entityMetadata, [$ownershipMetadata->getOwnerFieldName() => null]);
        $entityMetadata->expects(self::once())
            ->method('getFieldValue')
            ->with($this->testEntity, $ownershipMetadata->getOwnerFieldName())
            ->willReturn($owner);
        $entityMetadata->expects(self::once())
            ->method('getIdentifierValues')
            ->with($this->testEntity)
            ->willReturn([]);

        $this->ownershipMetadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with(Entity::class)
            ->willReturn($ownershipMetadata);

        $this->expectAddOneShotIsGrantedObserver($accessLevel);
        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('CREATE', 'entity:' . Entity::class)
            ->willReturn(true);
        $this->businessUnitManager->expects(self::never())
            ->method('canUserBeSetAsOwner');

        $this->validator->validate($this->testEntity, $this->constraint);
        $this->assertNoViolation();
    }

    public function testInvalidNewEntityWithNewUserOwner(): void
    {
        $ownershipMetadata = $this->createOwnershipMetadata('USER');
        $entityMetadata = $this->createMock(ClassMetadata::class);
        $accessLevel = AccessLevel::DEEP_LEVEL;

        $owner = new User();
        $owner->setOrganization(new Organization());
        $this->testEntity->setOwner($owner);
        $this->testEntity->setOrganization($this->currentOrg);

        $this->expectManageableEntity($entityMetadata, [$ownershipMetadata->getOwnerFieldName() => null]);
        $entityMetadata->expects(self::exactly(2))
            ->method('getFieldValue')
            ->willReturnMap([
                [$this->testEntity, $ownershipMetadata->getOwnerFieldName(), $owner],
                [
                    $this->testEntity,
                    $ownershipMetadata->getOrganizationFieldName(),
                    $this->testEntity->getOrganization()
                ]
            ]);
        $entityMetadata->expects(self::once())
            ->method('getIdentifierValues')
            ->with($this->testEntity)
            ->willReturn([]);

        $this->ownershipMetadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with(Entity::class)
            ->willReturn($ownershipMetadata);

        $this->expectAddOneShotIsGrantedObserver($accessLevel);
        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('CREATE', 'entity:' . Entity::class)
            ->willReturn(true);
        $this->businessUnitManager->expects(self::never())
            ->method('canUserBeSetAsOwner');

        $this->validator->validate($this->testEntity, $this->constraint);
        $this->buildViolation($this->constraint->message)
            ->atPath('owner')
            ->setParameters(['{{ owner }}' => 'owner'])
            ->assertRaised();
    }

    public function testInvalidNewEntityWithNewBusinessUnitOwner(): void
    {
        $ownershipMetadata = $this->createOwnershipMetadata('BUSINESS_UNIT');
        $entityMetadata = $this->createMock(ClassMetadata::class);
        $accessLevel = AccessLevel::DEEP_LEVEL;

        $owner = new BusinessUnit();
        $owner->setOrganization(new Organization());
        $this->testEntity->setOwner($owner);
        $this->testEntity->setOrganization($this->currentOrg);

        $this->expectManageableEntity($entityMetadata, [$ownershipMetadata->getOwnerFieldName() => null]);
        $entityMetadata->expects(self::exactly(2))
            ->method('getFieldValue')
            ->willReturnMap([
                [$this->testEntity, $ownershipMetadata->getOwnerFieldName(), $owner],
                [
                    $this->testEntity,
                    $ownershipMetadata->getOrganizationFieldName(),
                    $this->testEntity->getOrganization()
                ]
            ]);
        $entityMetadata->expects(self::once())
            ->method('getIdentifierValues')
            ->with($this->testEntity)
            ->willReturn([]);

        $this->ownershipMetadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with(Entity::class)
            ->willReturn($ownershipMetadata);

        $this->expectAddOneShotIsGrantedObserver($accessLevel);
        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('CREATE', 'entity:' . Entity::class)
            ->willReturn(true);
        $this->businessUnitManager->expects(self::never())
            ->method('canBusinessUnitBeSetAsOwner');

        $this->validator->validate($this->testEntity, $this->constraint);
        $this->buildViolation($this->constraint->message)
            ->atPath('owner')
            ->setParameters(['{{ owner }}' => 'owner'])
            ->assertRaised();
    }

    public function testValidNewEntityWithBusinessUnitOwnerAndWithoutUserInToken(): void
    {
        $tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $tokenAccessor->expects(self::any())
            ->method('getUser')
            ->willReturn(null);

        $this->validator = new OwnerValidator(
            $this->doctrine,
            $this->ownershipMetadataProvider,
            $this->authorizationChecker,
            $tokenAccessor,
            $this->ownerTreeProvider,
            $this->aclVoter,
            $this->aclGroupProvider,
            $this->businessUnitManager
        );

        $ownershipMetadata = $this->createOwnershipMetadata('BUSINESS_UNIT');
        $entityMetadata = $this->createMock(ClassMetadata::class);
        $accessLevel = AccessLevel::DEEP_LEVEL;

        $owner = $this->createBusinessUnit(123);
        $this->testEntity->setOwner($owner);

        $this->expectManageableEntity($entityMetadata, [$ownershipMetadata->getOwnerFieldName() => null]);
        $entityMetadata->expects(self::once())
            ->method('getFieldValue')
            ->with($this->testEntity, $ownershipMetadata->getOwnerFieldName())
            ->willReturn($owner);
        $entityMetadata->expects(self::once())
            ->method('getIdentifierValues')
            ->with($this->testEntity)
            ->willReturn([]);

        $this->ownershipMetadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with(Entity::class)
            ->willReturn($ownershipMetadata);

        $this->expectAddOneShotIsGrantedObserver($accessLevel);
        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('CREATE', 'entity:' . Entity::class)
            ->willReturn(true);
        $this->businessUnitManager->expects(self::never())
            ->method('canBusinessUnitBeSetAsOwner');

        $this->validator->validate($this->testEntity, $this->constraint);
        $this->assertNoViolation();
    }
}
