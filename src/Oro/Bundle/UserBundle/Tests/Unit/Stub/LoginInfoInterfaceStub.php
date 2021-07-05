<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Stub;

use Oro\Bundle\SecurityBundle\Model\Role;
use Oro\Bundle\UserBundle\Entity\LoginInfoInterface;
use Oro\Bundle\UserBundle\Entity\UserInterface;

class LoginInfoInterfaceStub implements UserInterface, LoginInfoInterface
{
    /** @var \DateTime */
    protected $lastLogin;

    /** @var int */
    protected $loginCount;

    /** @var string */
    protected $username;

    /** @var string */
    protected $password;

    /** @var string */
    protected $plainPassword;

    /**
     * {@inheritdoc}
     */
    public function isAccountNonLocked(): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function isEnabled(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getLastLogin()
    {
        return $this->lastLogin;
    }

    /**
     * {@inheritdoc}
     */
    public function getLoginCount()
    {
        return $this->loginCount;
    }

    /**
     * {@inheritdoc}
     */
    public function setLastLogin(\DateTime $time)
    {
        $this->lastLogin = $time;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setLoginCount($count)
    {
        $this->loginCount = $count;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setUsername($username): self
    {
        $this->username = $username;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setPassword(?string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getSalt()
    {
        return 'salt';
    }

    /**
     * {@inheritdoc}
     */
    public function setPlainPassword(?string $password): self
    {
        $this->plainPassword = $password;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getRoles()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getUserRoles(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * {@inheritdoc}
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * {@inheritdoc}
     */
    public function eraseCredentials()
    {
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    /**
     * {@inheritdoc}
     */
    public function addUserRole(Role $role): self
    {
        return $this;
    }
}
