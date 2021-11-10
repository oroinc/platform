<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Log\Processor\Stub;

use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use ProxyManager\Proxy\LazyLoadingInterface;

class MessageProcessorLazyLoadingProxy extends MessageProcessorProxy implements LazyLoadingInterface
{
    private bool $isProxyInitialized;

    public function __construct(MessageProcessorInterface $messageProcessor, bool $isProxyInitialized)
    {
        parent::__construct($messageProcessor);

        $this->isProxyInitialized = $isProxyInitialized;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getWrappedValueHolderValue() : ?object
    {
        return $this->isProxyInitialized ? parent::getWrappedValueHolderValue() : null;
    }

    public function setProxyInitializer(?\Closure $initializer = null): void
    {
    }

    public function getProxyInitializer(): ?\Closure
    {
        return null;
    }

    public function initializeProxy(): bool
    {
        $this->isProxyInitialized = true;

        return $this->isProxyInitialized;
    }

    public function isProxyInitialized(): bool
    {
        return $this->isProxyInitialized;
    }
}
