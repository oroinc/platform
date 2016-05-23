<?php

namespace Oro\Bundle\OrganizationBundle\Entity\Ownership;

use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;

trait BusinessUnitAwareTrait
{
    use OrganizationAwareTrait;

    /**
     * @var BusinessUnit
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\OrganizationBundle\Entity\BusinessUnit", cascade={"persist"})
     * @ORM\JoinColumn(name="business_unit_owner_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $owner;

    /**
     * @return BusinessUnit|null
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * @param BusinessUnit|null $owner
     * @return $this
     */
    public function setOwner(BusinessUnit $owner = null)
    {
        $this->owner = $owner;

        return $this;
    }
}
