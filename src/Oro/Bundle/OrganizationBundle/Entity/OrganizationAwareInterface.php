<?php

namespace Oro\Bundle\OrganizationBundle\Entity;

interface OrganizationAwareInterface
{
    /**
     * @param OrganizationInterface $organization
     */
    public function setOrganization(OrganizationInterface $organization);

    /**
     * @return OrganizationInterface
     */
    public function getOrganization();
}
