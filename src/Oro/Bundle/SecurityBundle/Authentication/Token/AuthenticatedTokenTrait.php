<?php

namespace Oro\Bundle\SecurityBundle\Authentication\Token;

use Oro\Bundle\UserBundle\Entity\AbstractUser;

/**
 * Trait that sets the user to inner variable and use the inner user roles as the token's roles list
 * to avoid deauthentication on user roles list change.
 *
 * Please add this trait to all new tokens created in the system.
 */
trait AuthenticatedTokenTrait
{
    /** @var AbstractUser */
    private $innerUser;

    /**
     * {@inheritdoc}
     */
    public function setUser($user)
    {
        if ($user instanceof AbstractUser) {
            $this->innerUser = $user;
        } else {
            $this->innerUser = null;
        }

        parent::setUser($user);
    }

    /**
     * {@inheritdoc}
     */
    public function getRoles()
    {
        if ($this->innerUser instanceof AbstractUser) {
            return $this->innerUser->getRoles();
        }

        return parent::getRoles();
    }

    /**
     * {@inheritdoc}
     */
    public function getRoleNames(): array
    {
        if ($this->innerUser instanceof AbstractUser) {
            $roleNames = [];
            foreach ($this->innerUser->getRoles() as $role) {
                $roleNames[] = (string) $role;
            }

            return $roleNames;
        }

        return parent::getRoleNames();
    }
}
