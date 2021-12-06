<?php

namespace Oro\Bundle\SecurityBundle\Tests\Functional\Acl;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Acl\Domain\DomainObjectReference;
use Oro\Bundle\SecurityBundle\Acl\Domain\DomainObjectWrapper;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Test\Functional\RolePermissionExtension;
use Oro\Bundle\TestFrameworkBundle\Entity\TestEntityWithUserOwnership as TestEntity;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadBusinessUnit;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Voter\FieldVote;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class LimitedAccessTest extends WebTestCase
{
    use RolePermissionExtension;

    /** @var TestEntity */
    private $testEntity;

    protected function setUp(): void
    {
        $userName = 'test_user';
        $userEmail = 'test_user@example.com';
        $userPwd = 'testUserPwd123';

        $this->initClient([], $this->generateBasicAuthHeader($userName, $userPwd));
        $this->loadFixtures([LoadBusinessUnit::class]);

        /** @var EntityManagerInterface $em */
        $em = $this->getContainer()->get('doctrine')->getManagerForClass(User::class);

        $organization = $em->getRepository(Organization::class)->find(self::AUTH_ORGANIZATION);
        /** @var User $user */
        $user = $em->getRepository(User::class)->findOneBy(['email' => $userEmail]);
        if (null === $user) {
            /** @var UserManager $userManager */
            $userManager = $this->getContainer()->get('oro_user.manager');

            /** @var User $user */
            $user = $userManager->createUser();
            $role = $em->getRepository(Role::class)->findOneBy(['role' => 'ROLE_USER']);
            $user
                ->setOwner($this->getReference('business_unit'))
                ->setUsername($userName)
                ->setEmail($userEmail)
                ->setPlainPassword($userPwd)
                ->addUserRole($role)
                ->setOrganization($organization)
                ->addOrganization($organization)
                ->setFirstName('Test')
                ->setLastName('User')
                ->setSalt('');
            $userManager->updateUser($user);
        }

        $this->updateUserSecurityToken($user->getEmail());

        $this->testEntity = $em->getRepository(TestEntity::class)
            ->createQueryBuilder('e')
            ->orderBy('e.id')
            ->getQuery()
            ->setMaxResults(1)
            ->getOneOrNullResult();
        if (null === $this->testEntity) {
            $this->testEntity = new TestEntity();
            $this->testEntity
                ->setName('test')
                ->setOrganization($organization)
                ->setOwner($em->getRepository(User::class)->findOneBy(['email' => self::AUTH_USER]));
            $em->persist($this->testEntity);
            $em->flush();

            $this->updateRolePermissionsForField(
                'ROLE_USER',
                TestEntity::class,
                'name',
                [
                    'VIEW' => AccessLevel::SYSTEM_LEVEL,
                    'EDIT' => AccessLevel::BASIC_LEVEL
                ]
            );
        }
    }

    private function getAuthorizationChecker(): AuthorizationCheckerInterface
    {
        return $this->getContainer()->get('security.authorization_checker');
    }

    public function testActionByDescriptor()
    {
        self::assertFalse(
            $this->getAuthorizationChecker()->isGranted('EXECUTE;action:test_action')
        );
    }

    public function testActionByObjectIdentityDescriptor()
    {
        self::assertFalse(
            $this->getAuthorizationChecker()->isGranted('EXECUTE', 'action:test_action')
        );
    }

    public function testActionByAclAnnotation()
    {
        $aclAnnotation = new Acl(['id' => 'test_action', 'type' => 'action']);
        self::assertFalse(
            $this->getAuthorizationChecker()->isGranted('EXECUTE', $aclAnnotation)
        );
    }

    public function testActionByAclAnnotationId()
    {
        self::assertFalse(
            $this->getAuthorizationChecker()->isGranted('test_action')
        );
    }

    public function testEntityByDescriptor()
    {
        self::assertFalse(
            $this->getAuthorizationChecker()->isGranted('DELETE;entity:' . TestEntity::class)
        );
    }

    public function testEntityByObjectIdentityDescriptor()
    {
        self::assertFalse(
            $this->getAuthorizationChecker()->isGranted('DELETE', 'entity:' . TestEntity::class)
        );
    }

    public function testEntityWhenAccessGranted()
    {
        self::assertTrue(
            $this->getAuthorizationChecker()->isGranted('VIEW', 'entity:' . TestEntity::class)
        );
    }

    public function testEntityByAclAnnotation()
    {
        $aclAnnotation = new Acl([
            'id'         => 'test_entity_delete',
            'type'       => 'entity',
            'permission' => 'DELETE',
            'class'      => TestEntity::class
        ]);
        self::assertFalse(
            $this->getAuthorizationChecker()->isGranted('DELETE', $aclAnnotation)
        );
    }

    public function testEntityByAclAnnotationId()
    {
        self::assertFalse(
            $this->getAuthorizationChecker()->isGranted('test_entity_delete')
        );
    }

    public function testEntityRecord()
    {
        self::assertFalse(
            $this->getAuthorizationChecker()->isGranted('DELETE', $this->testEntity)
        );
    }

    public function testEntityRecordWhenAccessGranted()
    {
        self::assertTrue(
            $this->getAuthorizationChecker()->isGranted('VIEW', $this->testEntity)
        );
    }

    public function testEntityRecordByDomainObjectReference()
    {
        $objectReference = new DomainObjectReference(
            TestEntity::class,
            $this->testEntity->getId(),
            $this->testEntity->getOwner()->getId(),
            $this->testEntity->getOrganization()->getId()
        );
        self::assertFalse(
            $this->getAuthorizationChecker()->isGranted('DELETE', $objectReference)
        );
    }

    public function testEntityRecordByDomainObjectReferenceWhenAccessGranted()
    {
        $objectReference = new DomainObjectReference(
            TestEntity::class,
            $this->testEntity->getId(),
            $this->testEntity->getOwner()->getId(),
            $this->testEntity->getOrganization()->getId()
        );
        self::assertTrue(
            $this->getAuthorizationChecker()->isGranted('VIEW', $objectReference)
        );
    }

    public function testEntityRecordByDomainObjectWrapper()
    {
        $objectWrapper = new DomainObjectWrapper(
            $this->testEntity,
            new ObjectIdentity($this->testEntity->getId(), TestEntity::class)
        );
        self::assertFalse(
            $this->getAuthorizationChecker()->isGranted('DELETE', $objectWrapper)
        );
    }

    public function testEntityRecordByDomainObjectWrapperWhenAccessGranted()
    {
        $objectWrapper = new DomainObjectWrapper(
            $this->testEntity,
            new ObjectIdentity($this->testEntity->getId(), TestEntity::class)
        );
        self::assertTrue(
            $this->getAuthorizationChecker()->isGranted('VIEW', $objectWrapper)
        );
    }

    public function testEntityField()
    {
        self::assertFalse(
            $this->getAuthorizationChecker()->isGranted('EDIT', new FieldVote($this->testEntity, 'name'))
        );
    }

    public function testEntityFieldWhenAccessGranted()
    {
        self::assertTrue(
            $this->getAuthorizationChecker()->isGranted('VIEW', new FieldVote($this->testEntity, 'name'))
        );
    }

    public function testEntityFieldByDomainObjectReference()
    {
        $objectReference = new DomainObjectReference(
            TestEntity::class,
            $this->testEntity->getId(),
            $this->testEntity->getOwner()->getId(),
            $this->testEntity->getOrganization()->getId()
        );
        self::assertFalse(
            $this->getAuthorizationChecker()->isGranted('EDIT', new FieldVote($objectReference, 'name'))
        );
    }

    public function testEntityFieldByDomainObjectReferenceWhenAccessGranted()
    {
        $objectReference = new DomainObjectReference(
            TestEntity::class,
            $this->testEntity->getId(),
            $this->testEntity->getOwner()->getId(),
            $this->testEntity->getOrganization()->getId()
        );
        self::assertTrue(
            $this->getAuthorizationChecker()->isGranted('VIEW', new FieldVote($objectReference, 'name'))
        );
    }

    public function testEntityFieldByDomainObjectWrapper()
    {
        $objectReference = new DomainObjectWrapper(
            $this->testEntity,
            new ObjectIdentity($this->testEntity->getId(), TestEntity::class)
        );
        self::assertFalse(
            $this->getAuthorizationChecker()->isGranted('EDIT', new FieldVote($objectReference, 'name'))
        );
    }

    public function testEntityFieldByDomainObjectWrapperWhenAccessGranted()
    {
        $objectReference = new DomainObjectWrapper(
            $this->testEntity,
            new ObjectIdentity($this->testEntity->getId(), TestEntity::class)
        );
        self::assertTrue(
            $this->getAuthorizationChecker()->isGranted('VIEW', new FieldVote($objectReference, 'name'))
        );
    }
}
