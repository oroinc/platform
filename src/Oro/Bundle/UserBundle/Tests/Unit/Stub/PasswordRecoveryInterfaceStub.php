<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Stub;

use Oro\Bundle\SecurityBundle\Model\Role;
use Oro\Bundle\UserBundle\Entity\PasswordRecoveryInterface;
use Oro\Bundle\UserBundle\Entity\UserInterface;

class PasswordRecoveryInterfaceStub implements UserInterface, PasswordRecoveryInterface
{
    /** @var string */
    protected $username;

    /** @var string */
    protected $password;

    /** @var string */
    protected $plainPassword;

    /** @var string */
    protected $confirmationToken;

    /** @var string */
    protected $passwordRequestedAt;

    /** @var string */
    protected $passwordChangedAt;

    /**
     * {@inheritDoc}
     */
    public function setUsername($username): self
    {
        $this->username = $username;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setPassword(?string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getSalt()
    {
        return 'salt';
    }

    /**
     * {@inheritDoc}
     */
    public function setPlainPassword(?string $password): self
    {
        $this->plainPassword = $password;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getRoles()
    {
        return [];
    }

    /**
     * {@inheritDoc}
     */
    public function getUserRoles(): array
    {
        return [];
    }

    /**
     * {@inheritDoc}
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * {@inheritDoc}
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * {@inheritDoc}
     */
    public function eraseCredentials()
    {
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    /**
     * {@inheritDoc}
     */
    public function addUserRole(Role $role): self
    {
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function isPasswordRequestNonExpired($ttl)
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function getConfirmationToken()
    {
        return $this->confirmationToken;
    }

    /**
     * {@inheritDoc}
     */
    public function setConfirmationToken($token)
    {
        $this->confirmationToken = $token;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function generateToken()
    {
        return '';
    }

    /**
     * {@inheritDoc}
     */
    public function getPasswordRequestedAt()
    {
        return $this->passwordRequestedAt;
    }

    /**
     *{@inheritDoc}
     */
    public function setPasswordRequestedAt(\DateTime $time = null)
    {
        $this->passwordRequestedAt = $time;

        return $this;
    }

    /**
     *{@inheritDoc}
     */
    public function getPasswordChangedAt()
    {
        return $this->passwordChangedAt;
    }

    /**
     * {@inheritDoc}
     */
    public function setPasswordChangedAt(\DateTime $time = null)
    {
        $this->passwordChangedAt = $time;

        return $this;
    }
}
