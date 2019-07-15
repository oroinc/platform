<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Stub;

use Oro\Bundle\UserBundle\Entity\LoginInfoInterface;
use Oro\Bundle\UserBundle\Entity\UserInterface;
use Symfony\Component\Security\Core\Role\Role;

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
    public function isAccountNonExpired()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function isAccountNonLocked()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function isCredentialsNonExpired()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function isEnabled()
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
    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setPassword($password)
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
    public function setPlainPassword($password)
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
    public function getPlainPassword()
    {
        return $this->plainPassword;
    }

    /**
     * {@inheritdoc}
     */
    public function addRole(Role $role)
    {
        return $this;
    }
}
