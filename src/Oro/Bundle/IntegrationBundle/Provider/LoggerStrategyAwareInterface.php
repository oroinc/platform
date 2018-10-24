<?php

namespace Oro\Bundle\IntegrationBundle\Provider;

use Oro\Bundle\IntegrationBundle\Logger\LoggerStrategy;

/**
 * Inject LoggerStrategy into \Oro\Bundle\IntegrationBundle\Provider\SyncProcessorInterface
 */
interface LoggerStrategyAwareInterface
{
    /**
     * Get logger strategy
     *
     * @return LoggerStrategy
     */
    public function getLoggerStrategy();
}
