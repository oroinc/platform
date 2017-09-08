<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

/**
 * @ORM\Entity()
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
class TestEmployee extends TestPerson
{
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

    /**
     * @var string
     *
     * @ORM\Column(name="position", type="string", length=255)
     */
    protected $position;

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

    /**
     * @return string
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * @param string $position
     */
    public function setPosition($position)
    {
        $this->position = $position;
    }
}
