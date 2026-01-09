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

    #[\Override]
    public function getLastLogin()
    {
        return $this->lastLogin;
    }

    #[\Override]
    public function getLoginCount()
    {
        return $this->loginCount;
    }

    #[\Override]
    public function setLastLogin(\DateTime $time)
    {
        $this->lastLogin = $time;

        return $this;
    }

    #[\Override]
    public function setLoginCount($count)
    {
        $this->loginCount = $count;

        return $this;
    }

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
    public function eraseCredentials(): void
    {
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
    public function getUserIdentifier(): string
    {
        return $this->getUserIdentifier();
    }
}
