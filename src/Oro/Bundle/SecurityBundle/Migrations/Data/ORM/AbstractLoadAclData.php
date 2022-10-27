<?php

namespace Oro\Bundle\SecurityBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\SecurityBundle\Acl\Persistence\AclManager;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadRolesData;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Model\SecurityIdentityInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * The base class for data fixtures that load default permissions for roles.
 */
abstract class AbstractLoadAclData extends AbstractFixture implements
    DependentFixtureInterface,
    ContainerAwareInterface
{
    use ContainerAwareTrait;

    const ALL_ROLES = '*';

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [LoadRolesData::class];
    }

    public function load(ObjectManager $manager)
    {
        /** @var AclManager $aclManager */
        $aclManager = $this->container->get('oro_security.acl.manager');

        $rolesData = $this->getAclData();
        foreach ($rolesData as $roleName => $roleConfigData) {
            if (self::ALL_ROLES === $roleName) {
                foreach ($this->getRoles($manager) as $role) {
                    $this->processRole($aclManager, $manager, $role, $roleConfigData);
                }
            } else {
                $role = $this->getRole($manager, $roleName, $roleConfigData);
                if (null !== $role) {
                    $this->processRole($aclManager, $manager, $role, $roleConfigData);
                }
            }
        }

        $manager->flush();
        if ($aclManager->isAclEnabled()) {
            $aclManager->flush();
        }
    }

    /**
     * Gets path to load data from.
     *
     * @return string
     */
    abstract protected function getDataPath();

    /**
     * @param AclManager                $aclManager
     * @param SecurityIdentityInterface $sid
     * @param ObjectIdentity            $oid
     * @param string[]                  $permissions
     */
    protected function setPermissions(
        AclManager $aclManager,
        SecurityIdentityInterface $sid,
        ObjectIdentity $oid,
        array $permissions
    ) {
        $maskBuilders = $aclManager->getAllMaskBuilders($oid);
        foreach ($maskBuilders as $maskBuilder) {
            foreach ($permissions as $permission) {
                if ($maskBuilder->hasMaskForPermission($permission)) {
                    $maskBuilder->add($permission);
                }
            }
            $aclManager->setPermission($sid, $oid, $maskBuilder->get());
        }
    }

    /**
     * Returns ACL data as array
     *
     * Yaml File Example:
     *
     *     ROLE_NAME:
     *         bap_role: BAP_ROLE NAME
     *         label: Role Label
     *         permissions:
     *             entity|Some\Bundle\Entity\Name: [VIEW_SYSTEM, CREATE_SYSTEM, ...]
     *             action|some_acl_capability: [EXECUTE]
     *
     * @return array
     */
    protected function getAclData()
    {
        $fileName = $this->container
            ->get('kernel')
            ->locateResource($this->getDataPath());
        $fileName = str_replace('/', DIRECTORY_SEPARATOR, $fileName);

        return Yaml::parse(file_get_contents($fileName));
    }

    /**
     * Gets Role instance
     *
     * @param ObjectManager $objectManager
     * @param string        $roleName
     * @param array         $roleConfigData
     *
     * @return Role|null
     */
    protected function getRole(ObjectManager $objectManager, $roleName, $roleConfigData)
    {
        if (!empty($roleConfigData['bap_role'])) {
            $roleName = $roleConfigData['bap_role'];
        }

        return $objectManager->getRepository(Role::class)->findOneBy(['role' => $roleName]);
    }

    /**
     * Sets Role permissions
     *
     * @param AclManager    $aclManager
     * @param ObjectManager $objectManager
     * @param Role          $role
     * @param array         $roleConfigData
     */
    protected function processRole(AclManager $aclManager, $objectManager, Role $role, array $roleConfigData)
    {
        if (isset($roleConfigData['label'])) {
            $role->setLabel($roleConfigData['label']);
        }

        if (!$role->getId()) {
            $objectManager->persist($role);
        }

        if (isset($roleConfigData['permissions']) && $aclManager->isAclEnabled()) {
            $sid = $aclManager->getSid($role);
            foreach ($roleConfigData['permissions'] as $oid => $permissions) {
                $this->setPermissions(
                    $aclManager,
                    $sid,
                    $aclManager->getOid(str_replace('|', ':', $oid)),
                    $permissions
                );
            }
        }
    }

    /**
     * Returns all roles, some filter can be applied here
     *
     * @param ObjectManager $objectManager
     *
     * @return Role[]
     */
    protected function getRoles(ObjectManager $objectManager)
    {
        return $objectManager->getRepository(Role::class)
            ->createQueryBuilder('r')
            ->where('r.role <> :role')
            ->setParameter('role', User::ROLE_ANONYMOUS)
            ->getQuery()
            ->getResult();
    }
}
