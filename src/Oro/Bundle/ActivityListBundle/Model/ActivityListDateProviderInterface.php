<?php

namespace Oro\Bundle\ActivityListBundle\Model;

interface ActivityListDateProviderInterface
{
    /**
     * Can be updated date field on update activity entity
     *
     * @return bool
     */
    public function isDateUpdatable();
}
