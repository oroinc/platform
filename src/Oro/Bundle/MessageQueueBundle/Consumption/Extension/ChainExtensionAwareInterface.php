<?php

namespace Oro\Bundle\MessageQueueBundle\Consumption\Extension;

use Oro\Component\MessageQueue\Consumption\ExtensionInterface;

/**
 * Defines the contract for message queue consumption extensions that are aware of a chain extension.
 *
 * Implementations of this interface can receive a reference to the chain extension that contains
 * all other extensions, allowing them to interact with or delegate to other extensions in the chain.
 */
interface ChainExtensionAwareInterface
{
    /**
     * Sets an extension that contains all other extensions.
     */
    public function setChainExtension(ExtensionInterface $chainExtension);
}
