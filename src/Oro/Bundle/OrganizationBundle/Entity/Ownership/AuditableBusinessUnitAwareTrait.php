<?php

namespace Oro\Bundle\OrganizationBundle\Entity\Ownership;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;

/**
* AuditableBusinessUnitAware trait
*
*/
trait AuditableBusinessUnitAwareTrait
{
    use AuditableOrganizationAwareTrait;

    #[ORM\ManyToOne(targetEntity: BusinessUnit::class, cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'business_unit_owner_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true]])]
    protected ?BusinessUnit $owner = null;

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
    public function setOwner(?BusinessUnit $owner = null)
    {
        $this->owner = $owner;

        return $this;
    }
}
