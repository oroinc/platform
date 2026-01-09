<?php

namespace Oro\Bundle\EntityExtendBundle\Validator;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Bundle\FrameworkBundle\CacheWarmer\ValidatorCacheWarmer;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Config\Resource\ClassExistenceResource;
use Symfony\Component\Validator\ValidatorBuilder;
use Symfony\Component\VarExporter\VarExporter;

/**
 * Warms up entity validator mapping metadata with persistent cache usage instead of php array file.
 */
class PersistentValidatorCacheWarmer extends ValidatorCacheWarmer
{
    private const string DUMMY_DIR_TO_FAIL_IF_USED = '__VALIDATOR_CACHE_MUST_WRITE_TO_PERSISTENT_CACHE_ERROR__';

    public function __construct(
        private readonly CacheItemPoolInterface $persistentCache,
        ValidatorBuilder $validatorBuilder,
        string $phpArrayFile
    ) {
        // override cache to persistent instead of $phpArrayFile
        parent::__construct($validatorBuilder, $phpArrayFile);
    }

    #[\Override]
    public function warmUp(string $cacheDir, ?string $buildDir = null): array
    {
        $arrayAdapter = new ArrayAdapter();
        spl_autoload_register([ClassExistenceResource::class, 'throwOnRequiredClass']);
        try {
            if (!$this->doWarmUp($cacheDir, $arrayAdapter, $buildDir ?: self::DUMMY_DIR_TO_FAIL_IF_USED)) {
                return [];
            }
        } finally {
            spl_autoload_unregister([ClassExistenceResource::class, 'throwOnRequiredClass']);
        }
        $values = array_map(
            fn ($val) => null !== $val ? unserialize($val) : null,
            $arrayAdapter->getValues()
        );
        /** customization start */
        $this->persistentCache->clear();
        $preload = [];
        $isStaticValue = true;
        foreach ($values as $key => $value) {
            VarExporter::export($value, $isStaticValue, $preload);

            $cacheItem = $this->persistentCache->getItem($key);
            $cacheItem->set($value);
            $this->persistentCache->save($cacheItem);
        }

        return $preload;
        /** customization end */
    }
}
