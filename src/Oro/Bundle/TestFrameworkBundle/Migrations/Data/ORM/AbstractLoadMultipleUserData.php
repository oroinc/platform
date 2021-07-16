<?php

namespace Oro\Bundle\TestFrameworkBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\SecurityBundle\Acl\Persistence\AclManager;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserApi;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadAdminUserData;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * The base class for data fixtures that load users with their roles.
 */
abstract class AbstractLoadMultipleUserData extends AbstractFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    const ACL_PERMISSION = 'permission';
    const ACL_LEVEL = 'level';

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [LoadAdminUserData::class];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->loadRoles($manager);
        $this->loadUsers($manager);
    }

    protected function loadRoles(ObjectManager $manager)
    {
        /* @var AclManager $aclManager */
        $aclManager = $this->container->get('oro_security.acl.manager');

        foreach ($this->getRolesData() as $key => $items) {
            $role = new Role($key);
            $role->setLabel($key);
            $manager->persist($role);

            if ($aclManager->isAclEnabled()) {
                foreach ($items as $acls) {
                    $this->setRolePermissions($aclManager, $role, $acls['class'], $acls['acls']);
                }
            }

            $this->setReference($key, $role);
        }

        $manager->flush();
        if ($aclManager->isAclEnabled()) {
            $aclManager->flush();
        }
    }

    protected function loadUsers(ObjectManager $manager)
    {
        /* @var UserManager $userManager */
        $userManager = $this->container->get('oro_user.manager');

        $defaultUser = $this->getUser($manager);

        $businessUnit = $defaultUser->getOwner();
        $organization = $defaultUser->getOrganization();

        foreach ($this->getUsersData() as $item) {
            $user = $userManager->createUser();

            $apiKey = new UserApi();
            $apiKey
                ->setApiKey($item['password'])
                ->setUser($user)
                ->setOrganization($organization);

            $user
                ->setEmail($item['email'])
                ->setFirstName($item['firstname'])
                ->setLastName($item['lastname'])
                ->setBusinessUnits($defaultUser->getBusinessUnits())
                ->setOwner($businessUnit)
                ->setOrganization($organization)
                ->addOrganization($organization)
                ->setUsername($item['username'])
                ->setPlainPassword($item['password'])
                ->setEnabled(true)
                ->addApiKey($apiKey);

            foreach ($item['userRoles'] as $role) {
                /** @var Role $roleEntity */
                $roleEntity = $this->getReference($role);
                $user->addUserRole($roleEntity);
            }

            $userManager->updateUser($user);

            $this->setReference($user->getUsername(), $user);
        }
    }

    /**
     * @param AclManager $aclManager
     * @param Role $role
     * @param string $className
     * @param array $allowedAcls
     */
    protected function setRolePermissions(AclManager $aclManager, Role $role, $className, array $allowedAcls)
    {
        $sid = $aclManager->getSid($role);
        $oid = $aclManager->getOid('entity:' . $className);
        $maskBuilders = $aclManager->getAllMaskBuilders($oid);
        foreach ($maskBuilders as $maskBuilder) {
            foreach ($allowedAcls as $acl) {
                $permission = $acl[self::ACL_PERMISSION] . '_' . $acl[self::ACL_LEVEL];
                if ($maskBuilder->hasMaskForPermission($permission)) {
                    $maskBuilder->add($permission);
                }
            }
            $aclManager->setPermission($sid, $oid, $maskBuilder->get());
        }
    }

    /**
     * @param ObjectManager $manager
     * @return User
     * @throws \LogicException
     */
    protected function getUser(ObjectManager $manager)
    {
        /* @var User $user */
        $user = $manager->getRepository(User::class)->findOneBy([
            'email' => LoadAdminUserData::DEFAULT_ADMIN_EMAIL
        ]);
        if (!$user) {
            throw new \LogicException('There are no users in system');
        }

        return $user;
    }

    /**
     * @return array
     */
    abstract protected function getRolesData();

    /**
     * @return array
     */
    abstract protected function getUsersData();
}
