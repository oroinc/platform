<?php

namespace Oro\Bundle\ApiBundle\Provider;

use Oro\Bundle\ApiBundle\Config\EntityConfigMerger;

/**
 * The API resources configuration bag that collects the configuration
 * from all child configuration bags and returns the merged version of the configuration.
 */
class CombinedConfigBag implements ConfigBagInterface
{
    /** @var ConfigBagInterface[] */
    private $configBags;

    /** @var EntityConfigMerger */
    private $entityConfigMerger;

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

        return \array_unique(\array_merge(...$result));
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig(string $className, string $version): ?array
    {
        $configs = [];
        foreach ($this->configBags as $configBag) {
            $config = $configBag->getConfig($className, $version);
            if (!empty($config)) {
                $configs[] = $config;
            }
        }
        $count = \count($configs);
        if (0 === $count) {
            return null;
        }
        if (1 === $count) {
            return $configs[0];
        }

        $index = \count($configs) - 1;
        $result = $configs[$index];
        while ($index > 0) {
            $index--;
            $result = $this->entityConfigMerger->merge($configs[$index], $result);
        }

        return $result;
    }
}
