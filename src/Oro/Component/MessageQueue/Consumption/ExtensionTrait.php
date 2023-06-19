<?php

namespace Oro\Component\MessageQueue\Consumption;

/**
 * Implements all methods from ExtensionInterface with empty body.
 */
trait ExtensionTrait
{
    public function onStart(Context $context)
    {
    }

    public function onBeforeReceive(Context $context)
    {
    }

    public function onPreReceived(Context $context)
    {
    }

    public function onPostReceived(Context $context)
    {
    }

    public function onIdle(Context $context)
    {
    }

    public function onInterrupted(Context $context)
    {
    }
}
