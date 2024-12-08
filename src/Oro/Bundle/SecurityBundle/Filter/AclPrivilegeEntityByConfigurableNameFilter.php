<?php

namespace Oro\Bundle\SecurityBundle\Filter;

use Oro\Bundle\SecurityBundle\Model\AclPrivilege;
use Oro\Bundle\SecurityBundle\Model\ConfigurablePermission;

/**
 * Filters AclPrivilege by entity class and configurable name.
 */
class AclPrivilegeEntityByConfigurableNameFilter extends AclPrivilegeEntityFilter
{
    /** @var string */
    private $filteredConfigurableName;

    /** @var array */
    private $identityIds;

    public function __construct(string $filteredConfigurableName, array $entities = [])
    {
        $this->filteredConfigurableName = $filteredConfigurableName;

        foreach ($entities as $entity) {
            $this->addEntity($entity);
        }
    }

    public function addEntity(string $entity): void
    {
        $this->identityIds[] = 'entity:' . $entity;
    }

    #[\Override]
    public function filter(AclPrivilege $aclPrivilege, ConfigurablePermission $configurablePermission)
    {
        $result = parent::filter($aclPrivilege, $configurablePermission);

        return $result ? $configurablePermission->getName() !== $this->filteredConfigurableName : $result;
    }

    #[\Override]
    public function isSupported(AclPrivilege $aclPrivileges)
    {
        $identity = $aclPrivileges->getIdentity();

        return \in_array($identity->getId(), $this->identityIds, true);
    }
}
