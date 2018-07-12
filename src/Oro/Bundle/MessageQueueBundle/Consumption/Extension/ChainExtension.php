<?php

namespace Oro\Bundle\MessageQueueBundle\Consumption\Extension;

use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Consumption\ExtensionInterface;
use Oro\Component\MessageQueue\Log\ConsumerState;

/**
 * Delegates the handling of consumption to all child extensions
 */
class ChainExtension implements ResettableExtensionInterface, ChainExtensionAwareInterface
{
    /** @var ExtensionInterface[] */
    private $extensions;

    /** @var ConsumerState */
    private $consumerState;

    /**
     * @param ExtensionInterface[] $extensions
     * @param ConsumerState        $consumerState
     */
    public function __construct(array $extensions, ConsumerState $consumerState)
    {
        $this->extensions = $extensions;
        $this->consumerState = $consumerState;
        $this->setChainExtension($this);
    }

    /**
     * {@inheritdoc}
     */
    public function onStart(Context $context)
    {
        foreach ($this->extensions as $extension) {
            $this->consumerState->setExtension($extension);
            $extension->onStart($context);
        }
        $this->consumerState->setExtension();
    }

    /**
     * {@inheritdoc}
     */
    public function onBeforeReceive(Context $context)
    {
        foreach ($this->extensions as $extension) {
            $this->consumerState->setExtension($extension);
            $extension->onBeforeReceive($context);
        }
        $this->consumerState->setExtension();
    }

    /**
     * {@inheritdoc}
     */
    public function onPreReceived(Context $context)
    {
        foreach ($this->extensions as $extension) {
            $this->consumerState->setExtension($extension);
            $extension->onPreReceived($context);
        }
        $this->consumerState->setExtension();
    }

    /**
     * {@inheritdoc}
     */
    public function onPostReceived(Context $context)
    {
        foreach ($this->extensions as $extension) {
            $this->consumerState->setExtension($extension);
            $extension->onPostReceived($context);
        }
        $this->consumerState->setExtension();
    }

    /**
     * {@inheritdoc}
     */
    public function onIdle(Context $context)
    {
        foreach ($this->extensions as $extension) {
            $this->consumerState->setExtension($extension);
            $extension->onIdle($context);
        }
        $this->consumerState->setExtension();
    }

    /**
     * {@inheritdoc}
     */
    public function onInterrupted(Context $context)
    {
        foreach ($this->extensions as $extension) {
            $this->consumerState->setExtension($extension);
            $extension->onInterrupted($context);
        }
        $this->consumerState->setExtension();
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
