<?php

namespace Oro\Bundle\EntityExtendBundle\Cache;

use Oro\Bundle\EntityExtendBundle\Tools\EntityGenerator;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmer;

/**
 * Warmup cache for custom entity creation by config
 */
class CustomEntityCreationWarmer extends CacheWarmer
{
    public function __construct(protected EntityGenerator $entityGenerator, protected array $customEntities = [])
    {
    }

    #[\Override]
    public function warmUp($cacheDir, ?string $buildDir = null): array
    {
        if (empty($this->customEntities)) {
            return [];
        }
        $this->entityGenerator->generateCustomEntities($this->customEntities);

        return [];
    }

    #[\Override]
    public function isOptional(): bool
    {
        return false;
    }
}
