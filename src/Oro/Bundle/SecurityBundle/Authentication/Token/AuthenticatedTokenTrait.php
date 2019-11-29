<?php

namespace Oro\Bundle\SecurityBundle\Authentication\Token;

use Symfony\Component\Security\Core\User\AdvancedUserInterface;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Provides BC compatibility layer for tokens to make behavior of method setUser() like before Symfony 4.4.0.
 * It means authenticated user must not be deauthenticated when list of roles was changed.
 */
trait AuthenticatedTokenTrait
{
    /** @var UserInterface */
    private $newUser;

    /**
     * @return UserInterface|null
     */
    abstract public function getUser();

    /**
     * @return bool
     */
    abstract public function isAuthenticated();

    /**
     * @return array
     */
    abstract public function getRoleNames(): array;

    /**
     * @param UserInterface $user
     */
    public function setUser($user)
    {
        $this->newUser = $user;

        parent::setUser($user);

        $this->newUser = null;
    }

    /**
     * @param bool $authenticated
     */
    public function setAuthenticated($authenticated)
    {
        if (!$authenticated &&
            $this->newUser &&
            $this->isAuthenticated() &&
            $this->isUserRolesChanged($this->newUser) &&
            !$this->hasUserChanged($this->newUser)
        ) {
            return;
        }

        parent::setAuthenticated($authenticated);
    }

    /**
     * @param UserInterface $user
     * @return bool
     */
    private function isUserRolesChanged(UserInterface $user)
    {
        $userRoles = array_map('strval', (array) $user->getRoles());

        return \count($userRoles) !== \count($this->getRoleNames()) ||
            \count($userRoles) !== \count(array_intersect($userRoles, $this->getRoleNames()));
    }

    /**
     * @param UserInterface $user
     * @return bool
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function hasUserChanged(UserInterface $user): bool
    {
        if ($this->getUser() instanceof EquatableInterface) {
            return !(bool) $this->getUser()->isEqualTo($user);
        }

        if ($this->getUser()->getPassword() !== $user->getPassword()) {
            return true;
        }

        if ($this->getUser()->getSalt() !== $user->getSalt()) {
            return true;
        }

        if ($this->getUser()->getUsername() !== $user->getUsername()) {
            return true;
        }

        if ($this->getUser() instanceof AdvancedUserInterface && $user instanceof AdvancedUserInterface) {
            if ($this->getUser()->isAccountNonExpired() !== $user->isAccountNonExpired()) {
                return true;
            }

            if ($this->getUser()->isAccountNonLocked() !== $user->isAccountNonLocked()) {
                return true;
            }

            if ($this->getUser()->isCredentialsNonExpired() !== $user->isCredentialsNonExpired()) {
                return true;
            }

            if ($this->getUser()->isEnabled() !== $user->isEnabled()) {
                return true;
            }
        } elseif ($this->getUser() instanceof AdvancedUserInterface xor $user instanceof AdvancedUserInterface) {
            return true;
        }

        return false;
    }
}
