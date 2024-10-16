<?php

namespace Oro\Component\MessageQueue\Consumption;

class ChainExtension implements ExtensionInterface
{
    /** @var ExtensionInterface[] */
    private $extensions;

    /**
     * @param ExtensionInterface[] $extensions
     */
    public function __construct(array $extensions)
    {
        $this->extensions = $extensions;
    }

    #[\Override]
    public function onStart(Context $context)
    {
        foreach ($this->extensions as $extension) {
            $extension->onStart($context);
        }
    }

    #[\Override]
    public function onBeforeReceive(Context $context)
    {
        foreach ($this->extensions as $extension) {
            $extension->onBeforeReceive($context);
        }
    }

    #[\Override]
    public function onPreReceived(Context $context)
    {
        foreach ($this->extensions as $extension) {
            $extension->onPreReceived($context);
        }
    }

    #[\Override]
    public function onPostReceived(Context $context)
    {
        foreach ($this->extensions as $extension) {
            $extension->onPostReceived($context);
        }
    }

    #[\Override]
    public function onIdle(Context $context)
    {
        foreach ($this->extensions as $extension) {
            $extension->onIdle($context);
        }
    }

    #[\Override]
    public function onInterrupted(Context $context)
    {
        foreach ($this->extensions as $extension) {
            $extension->onInterrupted($context);
        }
    }
}
