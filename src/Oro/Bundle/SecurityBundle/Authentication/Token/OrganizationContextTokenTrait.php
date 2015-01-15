<?php

namespace Oro\Bundle\SecurityBundle\Authentication\Token;

use Oro\Bundle\OrganizationBundle\Entity\Organization;

trait OrganizationContextTokenTrait
{
    /** @var  Organization */
    protected $organization;

    /**
     * {@inheritdoc}
     */
    public function getOrganizationContext()
    {
        return $this->organization;
    }

    /**
     * {@inheritdoc}
     */
    public function setOrganizationContext(Organization $organization)
    {
        $this->organization = $organization;
    }
}
