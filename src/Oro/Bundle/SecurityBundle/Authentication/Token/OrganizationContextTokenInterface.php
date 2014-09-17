<?php

namespace Oro\Bundle\SecurityBundle\Authentication\Token;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

interface OrganizationContextTokenInterface extends TokenInterface
{
    /**
     * Returns organization
     *
     * @return Organization
     */
    public function getOrganizationContext();

    /**
     * Set an organization
     *
     * @param Organization $organization
     */
    public function setOrganizationContext(Organization $organization);
}
