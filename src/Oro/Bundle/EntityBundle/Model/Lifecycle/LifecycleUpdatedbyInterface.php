<?php

namespace Oro\Bundle\EntityBundle\Model\Lifecycle;

interface LifecycleUpdatedbyInterface
{
    public function getUpdatedBy();
    public function setUpdatedBy($updatedBy);
    public function isUpdatedUpdatedByProperty();
}
