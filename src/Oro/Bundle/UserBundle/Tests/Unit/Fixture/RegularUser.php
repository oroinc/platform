<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Fixture;

use Symfony\Component\Security\Core\User\UserInterface;

class RegularUser implements UserInterface
{
    /** @var array */
    protected $roles;

    /** @var string */
    protected $password;

    /** @var string */
    protected $salt;

    /** @var string */
    protected $username;

    /**
     * @return array
     */
    public function getRoles()
    {
        return $this->roles;
    }

    /**
     * @param array $roles
     */
    public function setRoles($roles)
    {
        $this->roles = $roles;
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param string $password
     */
    public function setPassword($password)
    {
        $this->password = $password;
    }

    /**
     * @return string
     */
    public function getSalt()
    {
        return $this->salt;
    }

    /**
     * @param string $salt
     */
    public function setSalt($salt)
    {
        $this->salt = $salt;
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @param string $username
     */
    public function setUsername($username)
    {
        $this->username = $username;
    }

    /**
     * {@inheritdoc}
     */
    public function eraseCredentials()
    {
        // TODO: Implement eraseCredentials() method.
    }
}
