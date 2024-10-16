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

    #[\Override]
    public function setUsername($username): self
    {
        $this->username = $username;

        return $this;
    }

    #[\Override]
    public function setPassword(?string $password): self
    {
        $this->password = $password;

        return $this;
    }

    #[\Override]
    public function getSalt(): ?string
    {
        return 'salt';
    }

    #[\Override]
    public function setPlainPassword(?string $password): self
    {
        $this->plainPassword = $password;

        return $this;
    }

    #[\Override]
    public function getRoles(): array
    {
        return [];
    }

    #[\Override]
    public function getUserRoles(): array
    {
        return [];
    }

    #[\Override]
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function getUsername()
    {
        return $this->username;
    }

    #[\Override]
    public function eraseCredentials()
    {
        return $this;
    }

    #[\Override]
    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    #[\Override]
    public function addUserRole(Role $role): self
    {
        return $this;
    }

    #[\Override]
    public function isPasswordRequestNonExpired($ttl)
    {
        return false;
    }

    #[\Override]
    public function getConfirmationToken()
    {
        return $this->confirmationToken;
    }

    #[\Override]
    public function setConfirmationToken($token)
    {
        $this->confirmationToken = $token;

        return $this;
    }

    #[\Override]
    public function generateToken()
    {
        return '';
    }

    #[\Override]
    public function getPasswordRequestedAt()
    {
        return $this->passwordRequestedAt;
    }

    #[\Override]
    public function setPasswordRequestedAt(\DateTime $time = null)
    {
        $this->passwordRequestedAt = $time;

        return $this;
    }

    #[\Override]
    public function getPasswordChangedAt()
    {
        return $this->passwordChangedAt;
    }

    #[\Override]
    public function setPasswordChangedAt(\DateTime $time = null)
    {
        $this->passwordChangedAt = $time;

        return $this;
    }

    #[\Override]
    public function getUserIdentifier(): string
    {
        return $this->getUsername();
    }
}
