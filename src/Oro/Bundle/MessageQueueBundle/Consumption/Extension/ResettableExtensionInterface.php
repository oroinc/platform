<?php

namespace Oro\Bundle\MessageQueueBundle\Consumption\Extension;

use Oro\Component\MessageQueue\Consumption\ExtensionInterface;

/**
 * This interface should be implemented be extensions that support clearing own internal state.
 */
interface ResettableExtensionInterface extends ExtensionInterface
{
    /**
     * Resets a state of the extension.
     */
    public function reset();
}
