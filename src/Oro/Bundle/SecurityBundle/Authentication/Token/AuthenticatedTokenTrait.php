<?php

namespace Oro\Bundle\SecurityBundle\Authentication\Token;

use Oro\Bundle\UserBundle\Entity\AbstractUser;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Trait that sets the user to inner variable and use the inner user roles as the token's roles list
 * to avoid deauthentication on user roles list change.
 *
 * Please add this trait to all new tokens created in the system.
 */
trait AuthenticatedTokenTrait
{
    /**
     * @deprecated. Will be renamed at 4.2 release
     * @var UserInterface
     */
    private $newUser;

    /**
     * @return UserInterface|null
     * @deprecated. Will be removed at 4.2 release
     */
    abstract public function getUser();

    /**
     * @return bool
     * @deprecated. Will be removed at 4.2 release
     */
    abstract public function isAuthenticated();

    /**
     * {@inheritdoc}
     */
    public function setUser($user)
    {
        if ($user instanceof AbstractUser) {
            $this->newUser = $user;
        } else {
            $this->newUser = null;
        }

        parent::setUser($user);
    }

    /**
     * {@inheritdoc}
     */
    public function getRoles()
    {
        if ($this->newUser instanceof AbstractUser) {
            return $this->newUser->getRoles();
        }

        return parent::getRoles();
    }

    /**
     * {@inheritdoc}
     */
    public function getRoleNames(): array
    {
        if ($this->newUser instanceof AbstractUser) {
            $roleNames = [];
            foreach ($this->newUser->getRoles() as $role) {
                $roleNames[] = (string) $role;
            }

            return $roleNames;
        }

        return parent::getRoleNames();
    }

    /**
     * @param bool $authenticated
     *
     * @deprecated. Will be removed at 4.2 release
     */
    public function setAuthenticated($authenticated)
    {
        parent::setAuthenticated($authenticated);
    }

    /**
     * @param UserInterface $user
     *
     * @return bool
     * @deprecated. Will be removed at 4.2 release
     */
    private function isUserRolesChanged(UserInterface $user)
    {
        $userRoles = array_map('strval', (array)$user->getRoles());

        return \count($userRoles) !== \count($this->getRoleNames()) ||
            \count($userRoles) !== \count(array_intersect($userRoles, $this->getRoleNames()));
    }

    /**
     * @param UserInterface $user
     *
     * @return bool
     * @deprecated. Will be removed at 4.2 release
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function hasUserChanged(UserInterface $user): bool
    {
        if ($this->getUser() instanceof EquatableInterface) {
            return !(bool)$this->getUser()->isEqualTo($user);
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
