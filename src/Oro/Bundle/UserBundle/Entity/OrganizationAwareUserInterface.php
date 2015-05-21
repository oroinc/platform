<?php

namespace Oro\Bundle\UserBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\OrganizationBundle\Entity\OrganizationAwareInterface;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;

interface OrganizationAwareUserInterface extends OrganizationAwareInterface
{
    /**
     * Get User Organizations
     *
     * @param  bool $onlyActive Returns enabled organizations only
     * @return ArrayCollection|OrganizationInterface[]
     */
    public function getOrganizations($onlyActive = false);
}
