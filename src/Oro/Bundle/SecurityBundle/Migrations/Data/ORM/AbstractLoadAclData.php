<?php

namespace Oro\Bundle\SecurityBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\SecurityBundle\Acl\Persistence\AclManager;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\Security\Acl\Model\SecurityIdentityInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Class provides functional for loading default Role permissions
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
        return [
            'Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadRolesData',
        ];
    }

    /**
     * Load roles default acls
     *
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        /** @var AclManager $aclManager */
        $aclManager = $this->container->get('oro_security.acl.manager');
        if (!$aclManager->isAclEnabled()) {
            return;
        }

        $rolesData = $this->getAclData();

        foreach ($rolesData as $roleName => $roleConfigData) {
            if (self::ALL_ROLES === $roleName) {
                foreach ($this->getRoles($manager) as $role) {
                    $this->processRole($aclManager, $manager, $role, $roleConfigData);
                }
            } else {
                $role = $this->getRole($manager, $roleName, $roleConfigData);
                if (!$role) {
                    continue;
                }

                $this->processRole($aclManager, $manager, $role, $roleConfigData);
            }
        }

        $aclManager->flush();
    }

    /**
     * Gets path to load data from.
     *
     * @return string
     */
    abstract protected function getDataPath();

    /**
     * Sets ACL
     *
     * @param AclManager $aclManager
     * @param mixed      $sid
     * @param string     $permission
     * @param array      $acls
     */
    protected function processPermission(
        AclManager $aclManager,
        SecurityIdentityInterface $sid,
        $permission,
        array $acls
    ) {
        $oid = $aclManager->getOid(str_replace('|', ':', $permission));

        $extension = $aclManager->getExtensionSelector()->select($oid);
        $maskBuilders = $extension->getAllMaskBuilders();

        foreach ($maskBuilders as $maskBuilder) {
            $mask = $maskBuilder->reset()->get();

            if (!empty($acls)) {
                foreach ($acls as $acl) {
                    if ($maskBuilder->hasMask('MASK_' . $acl)) {
                        $mask = $maskBuilder->add($acl)->get();
                    }
                }
            }

            $aclManager->setPermission($sid, $oid, $mask);
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

        return $objectManager->getRepository('OroUserBundle:Role')
            ->findOneBy(['role' => $roleName]);
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

        if (isset($roleConfigData['permissions'])) {
            $sid = $aclManager->getSid($role);
            foreach ($roleConfigData['permissions'] as $permission => $acls) {
                $this->processPermission($aclManager, $sid, $permission, $acls);
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
        return $objectManager->getRepository('OroUserBundle:Role')
            ->createQueryBuilder('r')
            ->where('r.role <> :role')
            ->setParameter('role', User::ROLE_ANONYMOUS)
            ->getQuery()
            ->getResult();
    }
}
