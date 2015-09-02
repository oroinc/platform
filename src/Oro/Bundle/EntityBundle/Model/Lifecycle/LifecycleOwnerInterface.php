<?php

namespace Oro\Bundle\EntityBundle\Model\Lifecycle;

use Oro\Bundle\UserBundle\Entity\User;

interface LifecycleOwnerInterface
{
    public function getOwner();
    public function setOwner(User $owner);
}
