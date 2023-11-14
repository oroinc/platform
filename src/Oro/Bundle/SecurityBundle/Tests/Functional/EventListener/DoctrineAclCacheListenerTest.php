<?php

namespace Oro\Bundle\SecurityBundle\Tests\Functional\EventListener;

use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\SecurityBundle\Cache\DoctrineAclCacheProvider;
use Oro\Bundle\SecurityBundle\EventListener\DoctrineAclCacheListener;
use Oro\Bundle\SecurityBundle\Tests\Functional\DataFixtures\LoadRolesData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\Group;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class DoctrineAclCacheListenerTest extends WebTestCase
{
    /** @var DoctrineAclCacheProvider|\PHPUnit\Framework\MockObject\MockObject  */
    private $queryCacheProvider;

    /** @var DoctrineAclCacheListener */
    private $listener;

    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());

        $this->loadFixtures([
            '@OroSecurityBundle/Tests/Functional/DataFixtures/load_test_data.yml',
            LoadRolesData::class
        ]);

        $this->queryCacheProvider = $this->createMock(DoctrineAclCacheProvider::class);

        $container = self::getContainer();
        $this->listener = new DoctrineAclCacheListener(
            $this->queryCacheProvider,
            $container->get('oro_security.ownership_tree_provider')
        );

        $container->get('doctrine')->getManager()->getEventManager()->addEventListener('onFlush', $this->listener);
    }

    private function expectUpdatedUsers(array $expectedUserIds): void
    {
        $this->queryCacheProvider->expects(self::once())
            ->method('clearForEntities')
            ->willReturnCallback(function (string $className, array $userIds) use ($expectedUserIds) {
                self::assertEquals(User::class, $className);
                self::assertCount(count($expectedUserIds), $userIds);
                self::assertEquals([], array_diff($expectedUserIds, $userIds));
            });
    }

    private function persistEntity(object $entity): void
    {
        $em = self::getContainer()->get('doctrine')->getManager();

        $em->persist($entity);
        $em->flush();
    }

    private function deleteEntity(object $entity): void
    {
        $em = self::getContainer()->get('doctrine')->getManager();

        $em->remove($entity);
        $em->flush();
    }

    public function testOnNewNonSupportedEntity(): void
    {
        $entity = new Group();
        $entity->setOwner($this->getReference('bu_first_child1'))
            ->setOrganization($this->getReference('organization'))
            ->setName('test group');

        $this->queryCacheProvider->expects(self::never())
            ->method('clearForEntities');

        $this->persistEntity($entity);
    }

    public function testAddNewUserWithBusinessUnit(): void
    {
        $businessUnit = $this->getReference('bu_first_child1');
        $organization = $this->getReference('organization');

        $user = new User();
        $user->setOwner($businessUnit)
            ->setOrganization($organization)
            ->addOrganization($organization)
            ->addBusinessUnit($businessUnit)
            ->setUsername('test_user')
            ->setPlainPassword('oihiu&*hu876&G')
            ->setEmail('test@test.com');

        $this->expectUpdatedUsers([
            $this->getReference('user_with_admin_role')->getId(),
            $this->getReference('user_has_all_bu')->getId(),
            $this->getReference('user_has_first_bu')->getId(),
            $this->getReference('user_has_first_child1_bu')->getId()
        ]);

        self::getContainer()->get('oro_user.manager')->updateUser($user);
    }

    public function testAddNewUserWithRole(): void
    {
        $businessUnit = $this->getReference('bu_first_child1');
        $organization = $this->getReference('organization');
        $role = $this->getReference('second_role');

        $user = new User();
        $user->setOwner($businessUnit)
            ->setOrganization($organization)
            ->addOrganization($organization)
            ->addUserRole($role)
            ->setUsername('test_user')
            ->setPlainPassword('oihiu&*hu876&G')
            ->setEmail('test@test.com');

        $this->queryCacheProvider->expects(self::once())
            ->method('clearForEntities')
            ->willReturnCallback(function (string $className, array $userIds) {
                self::assertEquals(User::class, $className);
                self::assertEquals([0 => null], $userIds);
            });

        self::getContainer()->get('oro_user.manager')->updateUser($user);
    }

    public function testAddNewNonRootBusinessUnitWithoutAddedUsers(): void
    {
        $parentBusinessUnit = $this->getReference('bu_first_child1');
        $organization = $this->getReference('organization');

        $businessUnit = new BusinessUnit();
        $businessUnit->setName('testBu')
            ->setOrganization($organization)
            ->setOwner($parentBusinessUnit);

        $this->queryCacheProvider->expects(self::never())
            ->method('clearForEntities');

        $this->persistEntity($businessUnit);
    }

    public function testAddNewNonRootBusinessUnitWithAddedUsers(): void
    {
        $parentBusinessUnit = $this->getReference('bu_first_child1');
        $organization = $this->getReference('organization');
        $user = $this->getReference('user_has_third_bu');

        $businessUnit = new BusinessUnit();
        $businessUnit->setName('testBu')
            ->setOrganization($organization)
            ->setOwner($parentBusinessUnit)
            ->addUser($user);

        $this->expectUpdatedUsers([
            $this->getReference('user_with_admin_role')->getId(),
            $this->getReference('user_has_all_bu')->getId(),
            $this->getReference('user_has_first_bu')->getId(),
            $this->getReference('user_has_first_child1_bu')->getId(),
            $user->getId()
        ]);

        $this->persistEntity($businessUnit);
    }

    public function testUpdateUserAddBusinessUnit(): void
    {
        $user = $this->getReference('user_has_third_bu');
        $businessUnit = $this->getReference('bu_first_child1');

        $user->addBusinessUnit($businessUnit);

        $this->expectUpdatedUsers([
            $this->getReference('user_with_admin_role')->getId(),
            $this->getReference('user_has_all_bu')->getId(),
            $this->getReference('user_has_first_bu')->getId(),
            $this->getReference('user_has_first_child1_bu')->getId(),
            $user->getId()
        ]);

        self::getContainer()->get('oro_user.manager')->updateUser($user);
    }

    public function testUpdateUserRemoveBusinessUnit(): void
    {
        /** @var User $user */
        $user = $this->getReference('user_has_first_child1_bu');
        $businessUnit = $this->getReference('bu_first_child1');

        $user->removeBusinessUnit($businessUnit);

        $this->expectUpdatedUsers([
            $this->getReference('user_with_admin_role')->getId(),
            $this->getReference('user_has_all_bu')->getId(),
            $this->getReference('user_has_first_bu')->getId(),
            $user->getId()
        ]);

        self::getContainer()->get('oro_user.manager')->updateUser($user);
    }

    public function testUpdateUserAddRole(): void
    {
        /** @var User $user */
        $user = $this->getReference('user_has_first_child1_bu');
        $role = $this->getReference('admin_role');

        $user->addUserRole($role);

        $this->expectUpdatedUsers([$user->getId()]);

        self::getContainer()->get('oro_user.manager')->updateUser($user);
    }

    public function testUpdateUserRemoveRole(): void
    {
        /** @var User $user */
        $user = $this->getReference('user_has_first_child1_bu');
        $role = $this->getReference('second_role');

        $user->removeUserRole($role);

        $this->expectUpdatedUsers([$user->getId()]);

        self::getContainer()->get('oro_user.manager')->updateUser($user);
    }

    public function testUpdateBusinessUnitChangeOwner(): void
    {
        $parentBusinessUnit = $this->getReference('bu_first_child1');
        $businessUnit = $this->getReference('bu_second');

        $businessUnit->setOwner($parentBusinessUnit);

        $this->expectUpdatedUsers([
            $this->getReference('user_with_admin_role')->getId(),
            $this->getReference('user_has_all_bu')->getId(),
            $this->getReference('user_has_first_bu')->getId(),
            $this->getReference('user_has_first_child1_bu')->getId(),
            $this->getReference('user_has_second_bu')->getId()
        ]);

        $this->persistEntity($businessUnit);
    }

    public function testDeleteBusinessUnit(): void
    {
        $businessUnit = $this->getReference('bu_first_child1');

        $this->expectUpdatedUsers([
            $this->getReference('user_with_admin_role')->getId(),
            $this->getReference('user_has_all_bu')->getId(),
            $this->getReference('user_has_first_bu')->getId(),
            $this->getReference('user_has_first_child1_bu')->getId(),
            $this->getReference('user_has_first_child1_child_bu')->getId()
        ]);

        $this->deleteEntity($businessUnit);
    }
}
