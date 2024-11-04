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

    #[\Override]
    public function process(MessageInterface $message, SessionInterface $session)
    {
    }

    #[\Override]
    public function getWrappedValueHolderValue(): ?object
    {
        return $this->isProxyInitialized ? parent::getWrappedValueHolderValue() : null;
    }

    #[\Override]
    public function setProxyInitializer(?\Closure $initializer = null): void
    {
    }

    #[\Override]
    public function getProxyInitializer(): ?\Closure
    {
        return null;
    }

    #[\Override]
    public function initializeProxy(): bool
    {
        $this->isProxyInitialized = true;

        return $this->isProxyInitialized;
    }

    #[\Override]
    public function isProxyInitialized(): bool
    {
        return $this->isProxyInitialized;
    }
}
