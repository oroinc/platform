<?php

namespace Oro\Bundle\SecurityBundle\Authentication\Token;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * The interface for the authentication tokens that information about the organization an user is logged in.
 */
interface OrganizationAwareTokenInterface extends TokenInterface
{
    /**
     * Gets the organization.
     *
     * @return Organization
     */
    public function getOrganization();

    /**
     * Sets the organization.
     */
    public function setOrganization(Organization $organization);
}
