<?php

namespace Oro\Bundle\CronBundle\Command;

interface ActiveCronCommandInterface
{
    /**
     * Checks if the command active (i.e. properly configured etc).
     *
     * @return bool
     */
    public function isActive();
}
