<?php

namespace Oro\Bundle\SecurityBundle\Model;

/**
 * Role is a simple implementation representing a role identified by a string.
 */
class Role
{
    private string $role;

    public function __construct(string $role)
    {
        $this->role = $role;
    }

    /**
     * Returns a string representation of the role.
     *
     * @return string
     */
    public function getRole()
    {
        return $this->role;
    }

    public function __toString(): string
    {
        return $this->role;
    }
}
