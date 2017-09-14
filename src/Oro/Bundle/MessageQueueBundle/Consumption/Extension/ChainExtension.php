<?php

namespace Oro\Bundle\MessageQueueBundle\Consumption\Extension;

use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Consumption\ExtensionInterface;

class ChainExtension implements ResettableExtensionInterface, ChainExtensionAwareInterface
{
    /** @var ExtensionInterface[] */
    private $extensions;

    /**
     * @param ExtensionInterface[] $extensions
     */
    public function __construct(array $extensions)
    {
        $this->extensions = $extensions;
        $this->setChainExtension($this);
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

    /**
     * {@inheritdoc}
     */
    public function reset()
    {
        foreach ($this->extensions as $extension) {
            if ($extension instanceof ResettableExtensionInterface) {
                $extension->reset();
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setChainExtension(ExtensionInterface $chainExtension)
    {
        foreach ($this->extensions as $extension) {
            if ($extension instanceof ChainExtensionAwareInterface) {
                $extension->setChainExtension($chainExtension);
            }
        }
    }
}
