<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Owner\Fixtures\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'tbl_user')]
class TestUser
{
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255, unique: true)]
    protected ?string $username = null;

    #[ORM\ManyToOne(targetEntity: TestBusinessUnit::class)]
    #[ORM\JoinColumn(name: 'owner_id', referencedColumnName: 'id')]
    protected ?TestBusinessUnit $owner = null;

    /**
     * @var Collection<int, TestBusinessUnit>
     */
    #[ORM\ManyToMany(targetEntity: TestBusinessUnit::class, inversedBy: 'users')]
    #[ORM\JoinTable(name: 'tbl_user_to_business_unit')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'business_unit_id', referencedColumnName: 'id')]
    protected ?Collection $businessUnits = null;

    /**
     * @var Collection<int, TestOrganization>
     */
    #[ORM\ManyToMany(targetEntity: TestOrganization::class, inversedBy: 'users')]
    #[ORM\JoinTable(name: 'tbl_user_to_organization')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'organization_id', referencedColumnName: 'id')]
    protected ?Collection $organizations = null;

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

    public function setBusinessUnits(Collection $businessUnits)
    {
        $this->businessUnits = $businessUnits;
    }

    public function addBusinessUnit(TestBusinessUnit $businessUnit)
    {
        if (!$this->businessUnits->contains($businessUnit)) {
            $this->businessUnits->add($businessUnit);
        }
    }

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

    public function setOrganizations(Collection $organizations)
    {
        $this->organizations = $organizations;
    }

    public function addOrganization(TestOrganization $organization)
    {
        if (!$this->organizations->contains($organization)) {
            $this->organizations->add($organization);
        }
    }

    public function removeOrganization(TestOrganization $organization)
    {
        if ($this->organizations->contains($organization)) {
            $this->organizations->removeElement($organization);
        }
    }
}
