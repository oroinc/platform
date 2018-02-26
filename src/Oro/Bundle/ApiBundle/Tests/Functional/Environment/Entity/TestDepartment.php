<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\TestFrameworkBundle\Entity\TestFrameworkEntityInterface;

/**
 * @ORM\Entity()
 * @ORM\Table(name="test_api_department")
 * @Config(
 *      defaultValues={
 *          "ownership"={
 *              "owner_type"="BUSINESS_UNIT",
 *              "owner_field_name"="owner",
 *              "owner_column_name"="business_unit_owner_id",
 *              "organization_field_name"="organization",
 *              "organization_column_name"="organization_id"
 *          },
 *          "security"={
 *              "type"="ACL",
 *              "field_acl_supported" = "true"
 *          }
 *      }
 * )
 */
class TestDepartment implements TestFrameworkEntityInterface
{
    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    protected $name;

    /**
     * @var TestDepartment|null
     *
     * @ORM\OneToMany(
     *     targetEntity="Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestPerson",
     *     mappedBy="department"
     * )
     */
    protected $staff;

    /**
     * @var BusinessUnit
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\OrganizationBundle\Entity\BusinessUnit")
     * @ORM\JoinColumn(name="business_unit_owner_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $owner;

    /**
     * @var Organization
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\OrganizationBundle\Entity\Organization")
     * @ORM\JoinColumn(name="organization_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $organization;

    public function __construct()
    {
        $this->staff = new ArrayCollection();
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
     * @return Collection|TestPerson[]
     */
    public function getStaff()
    {
        return $this->staff;
    }

    /**
     * @param Collection $staff
     *
     * @return self
     */
    public function setStaff(Collection $staff)
    {
        $this->staff = $staff;

        return $this;
    }

    /**
     * @param TestPerson $person
     *
     * @return self
     */
    public function addStaff(TestPerson $person)
    {
        if (!$this->staff->contains($person)) {
            $this->staff->add($person);
            $person->setDepartment($this);
        }

        return $this;
    }

    /**
     * @param TestPerson $person
     *
     * @return self
     */
    public function removeStaff(TestPerson $person)
    {
        if ($this->staff->contains($person)) {
            $this->staff->removeElement($person);
            $person->setDepartment(null);
        }

        return $this;
    }

    /**
     * @return BusinessUnit|null
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * @param BusinessUnit $owningBusinessUnit
     *
     * @return self
     */
    public function setOwner($owningBusinessUnit)
    {
        $this->owner = $owningBusinessUnit;

        return $this;
    }

    /**
     * @return Organization|null
     */
    public function getOrganization()
    {
        return $this->organization;
    }

    /**
     * @param Organization $organization
     *
     * @return self
     */
    public function setOrganization(Organization $organization = null)
    {
        $this->organization = $organization;

        return $this;
    }
}
