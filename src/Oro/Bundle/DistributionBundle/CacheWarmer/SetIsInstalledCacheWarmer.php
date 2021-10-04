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

    public function isOptional(): bool
    {
        return false;
    }

    public function warmUp($cacheDir): void
    {
        if (!$this->applicationState->isInstalled()) {
            $this->applicationState->setInstalled();
        }
    }
}
