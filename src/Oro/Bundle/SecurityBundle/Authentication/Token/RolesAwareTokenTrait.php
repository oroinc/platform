<?php

namespace Oro\Bundle\SecurityBundle\Authentication\Token;

use Oro\Bundle\SecurityBundle\Model\Role;

/**
 * Provides implementation of {@see \Oro\Bundle\SecurityBundle\Authentication\Token\RolesAwareTokenInterface}.
 */
trait RolesAwareTokenTrait
{
    /** @var Role[] */
    protected array $userRoles = [];

    /**
     * @param string[]|Role[] $roles
     *
     * @return string[]
     */
    protected function initRoles(array $roles = []): array
    {
        $roleNames = [];
        foreach ($roles as $role) {
            if (!$role instanceof Role) {
                $role = new Role((string) $role);
            }

            $this->userRoles[] = $role;
            $roleNames[] = (string)$role;
        }

        return $roleNames;
    }

    /**
     * {@inheritdoc}
     */
    public function getRoles(): array
    {
        return $this->userRoles;
    }

    /**
     * {@inheritdoc}
     */
    public function __serialize(): array
    {
        return [$this->userRoles, parent::__serialize()];
    }

    /**
     * {@inheritdoc}
     */
    public function __unserialize(array $data): void
    {
        [$this->userRoles, $parentData] = $data;
        $parentData = \is_array($parentData) ? $parentData : unserialize($parentData);

        parent::__unserialize($parentData);
    }
}
