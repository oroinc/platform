<?php

namespace Oro\Bundle\UserBundle\Entity;

use BeSimple\SoapBundle\ServiceDefinition\Annotation as Soap;
use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\SoapBundle\Entity\SoapEntityInterface;

/**
 * @Soap\Alias("Oro.Bundle.UserBundle.Entity.User")
 */
class UserSoap extends User implements SoapEntityInterface
{
    /**
     * @Soap\ComplexType("int", nillable=true)
     */
    protected $id;

    /**
     * @Soap\ComplexType("string")
     */
    protected $username;

    /**
     * @Soap\ComplexType("string")
     */
    protected $email;

    /**
     * @Soap\ComplexType("string", nillable=true)
     */
    protected $namePrefix;

    /**
     * @Soap\ComplexType("string")
     */
    protected $firstName;

    /**
     * @Soap\ComplexType("string", nillable=true)
     */
    protected $middleName;

    /**
     * @Soap\ComplexType("string")
     */
    protected $lastName;

    /**
     * @Soap\ComplexType("string", nillable=true)
     */
    protected $nameSuffix;

    /**
     * @Soap\ComplexType("date", nillable=true)
     */
    protected $birthday;

    /**
     * @Soap\ComplexType("boolean")
     */
    protected $enabled;

    /**
     * @Soap\ComplexType("string", nillable=true)
     */
    protected $plainPassword;

    /**
     * @Soap\ComplexType("dateTime", nillable=true)
     */
    protected $lastLogin;

    /**
     * @Soap\ComplexType("int", nillable=true)
     */
    protected $owner;

    /**
     * @Soap\ComplexType("int[]", nillable=true)
     */
    protected $roles;

    /**
     * @Soap\ComplexType("int[]", nillable=true)
     */
    protected $groups;

    /**
     * @param User $user
     */
    public function soapInit($user)
    {
        $this->id = $user->id;
        $this->username = $user->username;
        $this->email = $user->email;
        $this->namePrefix = $user->namePrefix;
        $this->firstName = $user->firstName;
        $this->middleName = $user->middleName;
        $this->lastName = $user->lastName;
        $this->nameSuffix = $user->nameSuffix;
        $this->birthday = $user->birthday;
        $this->enabled = $user->enabled;
        $this->plainPassword = $user->plainPassword;
        $this->lastLogin = $user->lastLogin;
        $this->owner = $user->owner ? $user->owner->getId() : null;

        $this->roles = array();
        foreach ($user->getRoles() as $role) {
            $this->roles[] = $role->getId();
        }

        $this->groups = array();
        foreach ($user->getGroups() as $group) {
            $this->groups[] = $group->getId();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setRoles($roles)
    {
        $this->roles = $roles;
    }

    /**
     * {@inheritdoc}
     */
    public function getRoles()
    {
        return $this->roles;
    }
}
