<?php

namespace Oro\Bundle\SecurityBundle\ORM\Walker;

/**
 * Provides data for query acl access level check
 */
interface AclConditionDataBuilderInterface
{
    /**
     * @return array Returns empty array if entity has full access,
     *               array with null values if user doesn't have access to the entity
     *               and array with entity field and field values which user has access to.
     *               Array structure:
     *               0 - owner field name
     *               1 - owner values
     *               2 - organization field name
     *               3 - organization values
     *               4 - should owners be checked
     *                  (for example, in case of Organization ownership type, owners should not be checked)
     */
    public function getAclConditionData(string $entityClassName, string|array $permissions = 'VIEW'): ?array;
}
