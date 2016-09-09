<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Owner\Fixtures\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="tbl_user")
 */
class TestUser
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length=255, unique=true)
     */
    protected $username;

    /**
     * @ORM\ManyToOne(targetEntity="TestBusinessUnit")
     * @ORM\JoinColumn(name="owner_id", referencedColumnName="id")
     */
    protected $owner;

    /**
     * @ORM\ManyToMany(targetEntity="TestBusinessUnit", inversedBy="users")
     * @ORM\JoinTable(name="tbl_user_to_business_unit",
     *      joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="business_unit_id", referencedColumnName="id")}
     *  )
     */
    protected $businessUnits;

    /**
     * @ORM\ManyToMany(targetEntity="TestOrganization", inversedBy="users")
     * @ORM\JoinTable(name="tbl_user_to_organization",
     *      joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="organization_id", referencedColumnName="id")}
     *  )
     */
    protected $organizations;

    public function __construct()
    {
        $this->businessUnits = new ArrayCollection();
        $this->organizations = new ArrayCollection();
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
     * @return TestBusinessUnit
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * @param TestBusinessUnit $owner
     */
    public function setOwner($owner)
    {
        $this->owner = $owner;
    }

    /**
     * @return Collection
     */
    public function getBusinessUnits()
    {
        return $this->businessUnits;
    }

    /**
     * @param Collection $businessUnits
     */
    public function setBusinessUnits(Collection $businessUnits)
    {
        $this->businessUnits = $businessUnits;
    }

    /**
     * @param TestBusinessUnit $businessUnit
     */
    public function addBusinessUnit(TestBusinessUnit $businessUnit)
    {
        if (!$this->businessUnits->contains($businessUnit)) {
            $this->businessUnits->add($businessUnit);
        }
    }

    /**
     * @param TestBusinessUnit $businessUnit
     */
    public function removeBusinessUnit(TestBusinessUnit $businessUnit)
    {
        if ($this->businessUnits->contains($businessUnit)) {
            $this->businessUnits->removeElement($businessUnit);
        }
    }

    /**
     * @return Collection
     */
    public function getOrganizations()
    {
        return $this->organizations;
    }

    /**
     * @param Collection $organizations
     */
    public function setOrganizations(Collection $organizations)
    {
        $this->organizations = $organizations;
    }

    /**
     * @param TestOrganization $organization
     */
    public function addOrganization(TestOrganization $organization)
    {
        if (!$this->organizations->contains($organization)) {
            $this->organizations->add($organization);
        }
    }

    /**
     * @param TestOrganization $organization
     */
    public function removeOrganization(TestOrganization $organization)
    {
        if ($this->organizations->contains($organization)) {
            $this->organizations->removeElement($organization);
        }
    }
}
