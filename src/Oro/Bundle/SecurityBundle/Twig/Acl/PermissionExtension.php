<?php

namespace Oro\Bundle\SecurityBundle\Twig\Acl;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use Oro\Bundle\SecurityBundle\Acl\Permission\PermissionManager;
use Oro\Bundle\SecurityBundle\Entity\Permission;
use Oro\Bundle\SecurityBundle\Model\AclPermission;

class PermissionExtension extends \Twig_Extension
{
    const NAME = 'oro_security_acl_permission_extension';

    /** @var PermissionManager */
    protected $manger;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var Permission[] */
    private $permissions = [];

    /**
     * @param PermissionManager $manager
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(PermissionManager $manager, DoctrineHelper $doctrineHelper)
    {
        $this->manager = $manager;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @return array
     */
    public function getFunctions()
    {
        return array(
            'acl_permission' => new \Twig_Function_Method($this, 'getPermission'),
        );
    }

    /**
     * @param AclPermission $aclPermission
     * @return Permission
     */
    public function getPermission(AclPermission $aclPermission)
    {
        $name = $aclPermission->getName();

        if (!array_key_exists($name, $this->permissions)) {
            $this->permissions[$name] = null;

            $map = $this->manager->getPermissionsMap();
            if (!isset($map[$name])) {
                return;
            }

            $this->permissions[$name] = $this->doctrineHelper->getEntityManager('OroSecurityBundle:Permission')
                ->getReference('OroSecurityBundle:Permission', $map[$name]);
        }

        return $this->permissions[$name];
    }

    /**
     * @return string
     */
    public function getName()
    {
        return self::NAME;
    }
}
