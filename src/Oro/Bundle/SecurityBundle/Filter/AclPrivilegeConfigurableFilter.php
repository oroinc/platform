<?php

namespace Oro\Bundle\SecurityBundle\Filter;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\SecurityBundle\Acl\Permission\ConfigurablePermissionProvider;
use Oro\Bundle\SecurityBundle\Model\AclPrivilege;

/**
 * Delegates filtering of ACL privileges to child filters.
 */
class AclPrivilegeConfigurableFilter
{
    /** @var iterable|AclPrivilegeConfigurableFilterInterface[] */
    private $configurableFilters;

    /** @var ConfigurablePermissionProvider */
    private $configurablePermissionProvider;

    /**
     * @param iterable|AclPrivilegeConfigurableFilterInterface[] $configurableFilters
     * @param ConfigurablePermissionProvider                     $configurablePermissionProvider
     */
    public function __construct(
        iterable $configurableFilters,
        ConfigurablePermissionProvider $configurablePermissionProvider
    ) {
        $this->configurableFilters = $configurableFilters;
        $this->configurablePermissionProvider = $configurablePermissionProvider;
    }

    public function filter(ArrayCollection $aclPrivileges, string $configurableName): ArrayCollection
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
