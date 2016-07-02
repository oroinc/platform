<?php

namespace Oro\Bundle\ReminderBundle\Model;

use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;

interface OrganizationAwareReminderDataInterface
{
    /**
     * @return OrganizationInterface
     */
    public function getOrganization();
}
