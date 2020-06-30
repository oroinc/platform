<?php

namespace Oro\Bundle\SyncBundle\Authentication\Origin;

use Gos\Bundle\WebSocketBundle\Server\App\Registry\OriginRegistry as BaseOriginRegistry;

/**
 * Extends the origin registry to be able to use dynamically origins together with origins defined in the config.
 */
class OriginRegistry extends BaseOriginRegistry
{
    /** @var OriginProviderInterface */
    private $originProvider;

    /**
     * @param OriginProviderInterface $originProvider
     */
    public function __construct(OriginProviderInterface $originProvider)
    {
        parent::__construct();
        $this->originProvider = $originProvider;
    }

    /**
     * @return array
     */
    public function getOrigins(): array
    {
        return array_unique(array_merge(parent::getOrigins(), $this->originProvider->getOrigins()));
    }
}
