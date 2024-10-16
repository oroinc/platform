<?php

namespace Oro\Component\MessageQueue\Consumption;

/**
 * Abstract extension that implements all methods of the ExtensionInterface
 */
abstract class AbstractExtension implements ExtensionInterface
{
    #[\Override]
    public function onStart(Context $context)
    {
    }

    #[\Override]
    public function onBeforeReceive(Context $context)
    {
    }

    #[\Override]
    public function onPreReceived(Context $context)
    {
    }

    #[\Override]
    public function onPostReceived(Context $context)
    {
    }

    #[\Override]
    public function onIdle(Context $context)
    {
    }

    #[\Override]
    public function onInterrupted(Context $context)
    {
    }
}
