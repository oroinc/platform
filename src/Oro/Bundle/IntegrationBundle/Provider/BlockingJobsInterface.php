<?php

namespace Oro\Bundle\IntegrationBundle\Provider;

interface BlockingJobsInterface
{
    /**
     * Get array of command name that can block start command oro:cron:integration:sync
     *
     * @return array
     */
    public function getCommandName();
}
