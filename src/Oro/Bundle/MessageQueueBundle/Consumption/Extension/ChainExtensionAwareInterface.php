<?php

namespace Oro\Bundle\MessageQueueBundle\Consumption\Extension;

use Oro\Component\MessageQueue\Consumption\ExtensionInterface;

interface ChainExtensionAwareInterface
{
    /**
     * Sets an extension that contains all other extensions.
     */
    public function setChainExtension(ExtensionInterface $chainExtension);
}
