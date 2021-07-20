<?php

namespace Oro\Bundle\OrganizationBundle\Entity;

interface OrganizationAwareInterface
{
    public function setOrganization(OrganizationInterface $organization);

    /**
     * @return OrganizationInterface
     */
    public function getOrganization();
}
