<?php

namespace Oro\Component\Config\Resource;

use Symfony\Component\Config\Resource\ResourceInterface;
use Symfony\Component\Config\Resource\SelfCheckingResourceChecker as SymfonySelfCheckingResourceChecker;
use Symfony\Component\Config\ResourceCheckerInterface;

/**
 * Resource checker for instances of {@see SelfCheckingResourceInterface}.
 * The main difference with {@see \Symfony\Component\Config\Resource\SelfCheckingResourceChecker} is that it does not
 * use static local caching when debug is on.
 */
class SelfCheckingResourceChecker implements ResourceCheckerInterface
{
    private SymfonySelfCheckingResourceChecker $innerResourceChecker;

    private bool $debug;

    public function __construct(
        bool $debug,
        SymfonySelfCheckingResourceChecker $innerResourceChecker = null
    ) {
        $this->debug = $debug;
        $this->innerResourceChecker = $innerResourceChecker ?? new SymfonySelfCheckingResourceChecker();
    }

    public function supports(ResourceInterface $metadata): bool
    {
        return $this->innerResourceChecker->supports($metadata);
    }

    /**
     * @param \Symfony\Component\Config\Resource\SelfCheckingResourceInterface $resource
     *
     * {@inheritdoc}
     */
    public function isFresh(ResourceInterface $resource, int $timestamp): bool
    {
        if ($this->debug) {
            return $resource->isFresh($timestamp);
        }

        return $this->innerResourceChecker->isFresh($resource, $timestamp);
    }
}
