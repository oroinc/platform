<?php

namespace Oro\Bundle\OrganizationBundle\Entity;

/**
 * Defines the contract for entities that are aware of and associated with an organization.
 *
 * Implementing classes represent entities that belong to a specific organization and must
 * provide methods to set and retrieve their organization association. This is a core pattern
 * in the Oro platform for multi-organization support.
 */
interface OrganizationAwareInterface
{
    public function setOrganization(OrganizationInterface $organization);

    /**
     * @return OrganizationInterface
     */
    public function getOrganization();
}
