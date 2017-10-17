<?php

namespace Oro\Bundle\IntegrationBundle\Provider;

interface RedeliverableInterface
{
    /**
     * Job need to redeliver
     *
     * @return bool
     */
    public function needRedelivery();
}
