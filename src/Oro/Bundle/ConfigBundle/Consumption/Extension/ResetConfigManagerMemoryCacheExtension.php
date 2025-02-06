<?php

namespace Oro\Bundle\ConfigBundle\Consumption\Extension;

use Oro\Component\MessageQueue\Consumption\AbstractExtension;
use Oro\Component\MessageQueue\Consumption\Context;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Class for clearing the memory cache before message processing.
 */
class ResetConfigManagerMemoryCacheExtension extends AbstractExtension
{
    public function __construct(private ResetInterface $memoryCache)
    {
    }

    /**
     * Before starting message processing, it is necessary to ensure that the memory cache is cleared to avoid using
     * outdated data that might have been added before the consumer started.
     *
     * For example, the ConfigManager may store a global configuration in the cache, but when processing a message,
     * the "oro.security.token" indicates an organization-specific context. In this case, the cached configuration
     * may not match the scope in which the message is processed, leading to incorrect results.
     */
    public function onStart(Context $context): void
    {
        $this->memoryCache->reset();
    }
}
