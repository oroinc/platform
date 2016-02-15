<?php

namespace Oro\Bundle\SecurityBundle\Twig\Acl;

use Oro\Bundle\SecurityBundle\Acl\Permission\PermissionManager;
use Oro\Bundle\SecurityBundle\Entity\Permission;
use Oro\Bundle\SecurityBundle\Model\AclPermission;

class PermissionExtension extends \Twig_Extension
{
    const NAME = 'oro_security_acl_permission_extension';

    /** @var PermissionManager */
    private $manager;

    /**
     * @param PermissionManager $manager
     */
    public function __construct(PermissionManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * @return array
     */
    public function getFunctions()
    {
        return [
            'acl_permission' => new \Twig_Function_Method($this, 'getPermission'),
        ];
    }

    /**
     * @param AclPermission $aclPermission
     * @return Permission
     */
    public function getPermission(AclPermission $aclPermission)
    {
        return $this->manager->getPermissionByName($aclPermission->getName());
    }

    /**
     * @return string
     */
    public function getName()
    {
        return self::NAME;
    }
}
