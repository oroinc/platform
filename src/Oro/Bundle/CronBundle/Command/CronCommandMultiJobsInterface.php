<?php

namespace Oro\Bundle\CronBundle\Command;

interface CronCommandMultiJobsInterface
{
    /**
     * Define maximum number of jobs that could be run simultaneously.
     *
     * @return integer
     */
    public function getMaxJobsCount();
}
