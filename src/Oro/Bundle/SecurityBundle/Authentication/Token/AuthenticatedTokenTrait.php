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
    use RolesAwareTokenTrait {
        getRoles as protected getUserRoles;
    }

    /** @var AbstractUser */
    private $innerUser;

    /**
     * {@inheritdoc}
     */
    public function setUser($user): void
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
    public function getRoles(): array
    {
        if ($this->innerUser instanceof AbstractUser) {
            return $this->innerUser->getUserRoles();
        }

        return $this->getUserRoles();
    }

    /**
     * {@inheritdoc}
     */
    public function getRoleNames(): array
    {
        if ($this->innerUser instanceof AbstractUser) {
            return $this->innerUser->getRoles();
        }

        return parent::getRoleNames();
    }
}
