<?php

namespace Oro\Bundle\MessageQueueBundle\Consumption\Extension;

use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Consumption\ExtensionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * This class implements a resettable extension and it is used to wrap a specific extension
 * in order to reload it from the container after the reset is requested.
 */
class ResettableExtensionWrapper implements ResettableExtensionInterface
{
    /** @var ContainerInterface */
    private $container;

    /** @var string */
    private $extensionServiceId;

    /** @var ExtensionInterface|null */
    private $extension;

    /**
     * @param ContainerInterface $container
     * @param string             $extensionServiceId
     */
    public function __construct(ContainerInterface $container, $extensionServiceId)
    {
        $this->container = $container;
        $this->extensionServiceId = $extensionServiceId;
    }

    #[\Override]
    public function onStart(Context $context)
    {
        $this->getExtension()->onStart($context);
    }

    #[\Override]
    public function onBeforeReceive(Context $context)
    {
        $this->getExtension()->onBeforeReceive($context);
    }

    #[\Override]
    public function onPreReceived(Context $context)
    {
        $this->getExtension()->onPreReceived($context);
    }

    #[\Override]
    public function onPostReceived(Context $context)
    {
        $this->getExtension()->onPostReceived($context);
    }

    #[\Override]
    public function onIdle(Context $context)
    {
        $this->getExtension()->onIdle($context);
    }

    #[\Override]
    public function onInterrupted(Context $context)
    {
        $this->getExtension()->onInterrupted($context);
    }

    #[\Override]
    public function reset()
    {
        $this->extension = null;
    }

    /**
     * @return ExtensionInterface
     */
    public function getExtension()
    {
        if (null === $this->extension) {
            $this->extension = $this->container->get($this->extensionServiceId);
        }

        return $this->extension;
    }
}
