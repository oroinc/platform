<?php

namespace Oro\Bundle\UserBundle\Form\Provider;

use Oro\Bundle\UserBundle\Entity\Role;

/**
 * Interface for provider that returns set of roles can be used as user roles.
 */
interface RolesChoicesForUserProviderInterface
{
    /**
     * Returns a list of roles that can be select as user roles.
     */
    public function getRoles(): array;

    /**
     * Returns a label for given role.
     */
    public function getChoiceLabel(Role $role): string;
}
