<?php
namespace Oro\Component\MessageQueue\Consumption;

abstract class AbstractExtension implements ExtensionInterface
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
