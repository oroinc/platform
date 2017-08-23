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

    /**
     * {@inheritdoc}
     */
    public function onStart(Context $context)
    {
        foreach ($this->extensions as $extension) {
            $extension->onStart($context);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function onBeforeReceive(Context $context)
    {
        foreach ($this->extensions as $extension) {
            $extension->onBeforeReceive($context);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function onPreReceived(Context $context)
    {
        foreach ($this->extensions as $extension) {
            $extension->onPreReceived($context);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function onPostReceived(Context $context)
    {
        foreach ($this->extensions as $extension) {
            $extension->onPostReceived($context);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function onIdle(Context $context)
    {
        foreach ($this->extensions as $extension) {
            $extension->onIdle($context);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function onInterrupted(Context $context)
    {
        foreach ($this->extensions as $extension) {
            $extension->onInterrupted($context);
        }
    }
}
