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

    /**
     * {@inheritdoc}
     */
    public function isPasswordRequestNonExpired($ttl)
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfirmationToken()
    {
        return $this->confirmationToken;
    }

    /**
     * {@inheritdoc}
     */
    public function setConfirmationToken($token)
    {
        $this->confirmationToken = $token;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function generateToken()
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function getPasswordRequestedAt()
    {
        return $this->passwordRequestedAt;
    }

    /**
     *{@inheritdoc}
     */
    public function setPasswordRequestedAt(\DateTime $time = null)
    {
        $this->passwordRequestedAt = $time;

        return $this;
    }

    /**
     *{@inheritdoc}
     */
    public function getPasswordChangedAt()
    {
        return $this->passwordChangedAt;
    }

    /**
     * {@inheritdoc}
     */
    public function setPasswordChangedAt(\DateTime $time = null)
    {
        $this->passwordChangedAt = $time;

        return $this;
    }
}
