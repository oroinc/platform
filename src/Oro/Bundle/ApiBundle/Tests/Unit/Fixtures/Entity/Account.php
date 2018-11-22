<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="account_table")
 */
class Account
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer", name="id")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(name="name", type="string", length=50)
     */
    protected $name;

    /**
     * @ORM\ManyToMany(targetEntity="Role")
     * @ORM\JoinTable(name="account_to_role_table",
     *      joinColumns={@ORM\JoinColumn(name="group_id", referencedColumnName="id", onDelete="CASCADE")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="role_id", referencedColumnName="id", onDelete="CASCADE")}
     * )
     */
    protected $roles;

    /**
     * @ORM\ManyToMany(targetEntity="Contact", inversedBy="accounts")
     * @ORM\JoinTable(name="account_to_contact")
     */
    protected $contacts;

    public function __construct()
    {
        $this->roles = new ArrayCollection();
        $this->contacts = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return Role[]|Collection
     */
    public function getRoles()
    {
        return $this->roles;
    }

    /**
     * @param Role $role
     */
    public function addRole(Role $role)
    {
        if (!$this->roles->contains($role)) {
            $this->roles->add($role);
        }
    }

    /**
     * @param Role $role
     */
    public function removeRole(Role $role)
    {
        if ($this->roles->contains($role)) {
            $this->roles->removeElement($role);
        }
    }

    /**
     * @return Collection|Contact[]
     */
    public function getContacts()
    {
        return $this->contacts;
    }

    /**
     * @param Contact $contact
     */
    public function addContact(Contact $contact)
    {
        if (!$this->getContacts()->contains($contact)) {
            $this->getContacts()->add($contact);
            $contact->addAccount($this);
        }
    }

    /**
     * @param Collection $contacts
     */
    public function setContacts(Collection $contacts)
    {
        $this->contacts = $contacts;
    }

    /**
     * @param Contact $contact
     */
    public function removeContact(Contact $contact)
    {
        if ($this->getContacts()->contains($contact)) {
            $this->getContacts()->removeElement($contact);
            $contact->removeAccount($this);
        }
    }
}
