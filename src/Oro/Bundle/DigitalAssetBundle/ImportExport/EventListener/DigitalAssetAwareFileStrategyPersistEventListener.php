<?php

namespace Oro\Bundle\DigitalAssetBundle\ImportExport\EventListener;

use Doctrine\ORM\Event\PreFlushEventArgs;
use Oro\Bundle\CacheBundle\Provider\MemoryCache;

/**
 * Persists files collected by {@see DigitalAssetAwareFileStrategyEventListener}.
 */
class DigitalAssetAwareFileStrategyPersistEventListener
{
    public const FILES_WITH_DIGITAL_ASSETS_TO_PERSIST = 'filesWithDigitalAssetsToPersist';

    private MemoryCache $memoryCache;

    public function __construct(MemoryCache $memoryCache)
    {
        $this->memoryCache = $memoryCache;
    }

    public function preFlush(PreFlushEventArgs $args): void
    {
        if (!$this->memoryCache->has(self::FILES_WITH_DIGITAL_ASSETS_TO_PERSIST)) {
            return;
        }

        $files = $this->memoryCache->get(self::FILES_WITH_DIGITAL_ASSETS_TO_PERSIST);
        $this->memoryCache->delete(self::FILES_WITH_DIGITAL_ASSETS_TO_PERSIST);

        $entityManager = $args->getEntityManager();
        foreach ($files as $file) {
            if (!$entityManager->contains($file)) {
                // Skips files that are not going to be persisted.
                continue;
            }

            // Prevents uploading as the file will be reflected from digital asset.
            $file->setFile(null);

            $digitalAsset = $file->getDigitalAsset();
            if ($digitalAsset) {
                $entityManager->persist($digitalAsset);
            }
        }
    }
}
