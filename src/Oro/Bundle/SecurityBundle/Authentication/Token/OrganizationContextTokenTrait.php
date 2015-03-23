<?php

namespace Oro\Bundle\SecurityBundle\Authentication\Token;

use Oro\Bundle\OrganizationBundle\Entity\Organization;

trait OrganizationContextTokenTrait
{
    /** @var  Organization */
    protected $organization;

    /**
     * Returns organization
     *
     * @return Organization
     */
    public function getOrganizationContext()
    {
        return $this->organization;
    }

    /**
     * Set an organization
     *
     * @param Organization $organization
     */
    public function setOrganizationContext(Organization $organization)
    {
        $this->organization = $organization;
    }
}
