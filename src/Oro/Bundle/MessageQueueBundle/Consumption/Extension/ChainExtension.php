<?php

namespace Oro\Bundle\MessageQueueBundle\Consumption\Extension;

use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Consumption\ExtensionInterface;

use Oro\Bundle\MessageQueueBundle\Log\ConsumerState;

class ChainExtension implements ExtensionInterface
{
    /** @var ConsumerState */
    private $consumerState;

    /** @var ExtensionInterface[] */
    private $extensions;

    /**
     * @param ExtensionInterface[] $extensions
     * @param ConsumerState        $consumerState
     */
    public function __construct(array $extensions, ConsumerState $consumerState)
    {
        $this->extensions = $extensions;
        $this->consumerState = $consumerState;
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
}
