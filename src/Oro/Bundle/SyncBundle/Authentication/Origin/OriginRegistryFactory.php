<?php

namespace Oro\Bundle\SyncBundle\Authentication\Origin;

use Gos\Bundle\WebSocketBundle\Server\App\Registry\OriginRegistry;

/**
 * Create origin registry with pre-configured dynamic origins.
 */
class OriginRegistryFactory
{
    /**
     * @var OriginProviderInterface
     */
    private $originProvider;

    public function __construct(OriginProviderInterface $originProvider)
    {
        $this->originProvider = $originProvider;
    }

    public function __invoke(): OriginRegistry
    {
        $originRegistry = new OriginRegistry();
        foreach ($this->originProvider->getOrigins() as $origin) {
            $originRegistry->addOrigin($origin);
        }

        return $originRegistry;
    }
}
