<?php

namespace Oro\Bundle\SyncBundle\Authentication\Origin;

use Gos\Bundle\WebSocketBundle\Server\App\Registry\OriginRegistry;

/**
 * To use a dynamically origins together with hardcoded in config
 */
class OriginRegistryDecorator extends OriginRegistry
{
    /** @var OriginProviderInterface */
    private $originProvider;

    /** @var OriginRegistry */
    private $baseOriginRegistry;

    /**
     * @param OriginRegistry $baseOriginRegistry
     * @param OriginProviderInterface $originProvider
     */
    public function __construct(
        OriginRegistry $baseOriginRegistry,
        OriginProviderInterface $originProvider
    ) {
        $this->originProvider = $originProvider;
        $this->baseOriginRegistry = $baseOriginRegistry;
    }

    /**
     * @return array
     */
    public function getOrigins(): array
    {
        return array_unique(array_merge($this->baseOriginRegistry->getOrigins(), $this->originProvider->getOrigins()));
    }
}
