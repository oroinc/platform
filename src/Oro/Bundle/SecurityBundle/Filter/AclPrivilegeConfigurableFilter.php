<?php

namespace Oro\Bundle\SecurityBundle\Filter;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\SecurityBundle\Acl\Permission\ConfigurablePermissionProvider;
use Oro\Bundle\SecurityBundle\Model\AclPrivilege;

class AclPrivilegeConfigurableFilter
{
    /** @var  AclPrivilegeConfigurableFilterInterface[] */
    protected $configurableFilters = [];

    /** @var ConfigurablePermissionProvider */
    protected $configurablePermissionProvider;

    /**
     * @param ConfigurablePermissionProvider $configurablePermissionProvider
     */
    public function __construct(ConfigurablePermissionProvider $configurablePermissionProvider)
    {
        $this->configurablePermissionProvider = $configurablePermissionProvider;
    }

    /**
     * @param AclPrivilegeConfigurableFilterInterface $filter
     */
    public function addConfigurableFilter(AclPrivilegeConfigurableFilterInterface $filter)
    {
        $this->configurableFilters[] = $filter;
    }

    /**
     * @param ArrayCollection $aclPrivileges
     * @param string $configurableName
     *
     * @return ArrayCollection
     */
    public function filter(ArrayCollection $aclPrivileges, $configurableName)
    {
        $configurablePermission = $this->configurablePermissionProvider->get($configurableName);

        return $aclPrivileges->filter(
            function (AclPrivilege $aclPrivilege) use ($configurablePermission) {
                foreach ($this->configurableFilters as $filter) {
                    if ($filter->isSupported($aclPrivilege)) {
                        return $filter->filter($aclPrivilege, $configurablePermission);
                    }
                }

                return true;
            }
        );
    }
}
