<?php

namespace Oro\Bundle\SidebarBundle\Migrations\Data\ORM;

use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\OrganizationBundle\Migrations\Data\ORM\UpdateWithOrganization;

class UpdateDashboardsWithOrganization extends UpdateWithOrganization
{
    /**
     * Assign exists dashboards to the default organization
     *
     * @param \Doctrine\Common\Persistence\ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $this->update($manager, 'OroDashboardBundle:Dashboard');
    }
}
