<?php

namespace Oro\Bundle\DistributionBundle\CacheWarmer;

use Oro\Bundle\DistributionBundle\Handler\ApplicationState;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

/**
 * Sets isInstalled flag to true. Needed when we update the application from a version,
 * where the flag is not stored in the database yet.
 */
class SetIsInstalledCacheWarmer implements CacheWarmerInterface
{
    private ApplicationState $applicationState;

    public function __construct(ApplicationState $applicationState)
    {
        $this->applicationState = $applicationState;
    }

    #[\Override]
    public function isOptional(): bool
    {
        return false;
    }

    #[\Override]
    public function warmUp($cacheDir): array
    {
        $this->applicationState->setInstalled();

        return [];
    }
}
