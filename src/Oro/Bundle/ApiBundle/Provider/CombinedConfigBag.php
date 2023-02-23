<?php

namespace Oro\Bundle\ApiBundle\Provider;

use Oro\Bundle\ApiBundle\Config\EntityConfigMerger;
use Symfony\Contracts\Service\ResetInterface;

/**
 * The API resources configuration bag that collects the configuration
 * from all child configuration bags and returns the merged version of the configuration.
 */
class CombinedConfigBag implements ConfigBagInterface, ResetInterface
{
    /** @var ConfigBagInterface[] */
    private array $configBags;
    private EntityConfigMerger $entityConfigMerger;
    /** @var array [class name + version => config, ...] */
    private array $cache = [];

    /**
     * @param ConfigBagInterface[] $configBags
     * @param EntityConfigMerger   $entityConfigMerger
     */
    public function __construct(
        array $configBags,
        EntityConfigMerger $entityConfigMerger
    ) {
        $this->configBags = $configBags;
        $this->entityConfigMerger = $entityConfigMerger;
    }

    /**
     * {@inheritdoc}
     */
    public function getClassNames(string $version): array
    {
        $result = [];
        foreach ($this->configBags as $configBag) {
            $result[] = $configBag->getClassNames($version);
        }

        return array_unique(array_merge(...$result));
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig(string $className, string $version): ?array
    {
        $cacheKey = $className . '|' . $version;
        if (\array_key_exists($cacheKey, $this->cache)) {
            return $this->cache[$cacheKey];
        }

        $configs = [];
        foreach ($this->configBags as $configBag) {
            $config = $configBag->getConfig($className, $version);
            if ($config) {
                $configs[] = $config;
            }
        }

        $result = null;
        if ($configs) {
            $count = \count($configs);
            if (1 === $count) {
                $result = $configs[0];
            } else {
                $index = $count - 1;
                $result = $configs[$index];
                while ($index > 0) {
                    $index--;
                    $result = $this->entityConfigMerger->merge($configs[$index], $result);
                }
            }
        }

        $this->cache[$cacheKey] = $result;

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function reset(): void
    {
        foreach ($this->configBags as $configBag) {
            if ($configBag instanceof ResetInterface) {
                $configBag->reset();
            }
        }
        $this->cache = [];
    }
}
