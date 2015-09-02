<?php

namespace Oro\Bundle\EntityBundle\Model\Lifecycle;

use Oro\Bundle\UserBundle\Entity\User;

interface LifecycleUpdatedbyInterface
{
    public function getUpdatedBy();
    public function setUpdatedBy(User $updatedBy);
    public function isUpdatedUpdatedByProperty();
}
