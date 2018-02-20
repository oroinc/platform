<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Environment;

use Oro\Bundle\ApiBundle\Config\EntityConfigMerger;
use Oro\Bundle\ApiBundle\Provider\ConfigBagInterface;

class TestConfigBag implements ConfigBagInterface
{
    /** @var ConfigBagInterface */
    private $configBag;

    /** @var EntityConfigMerger */
    private $entityConfigMerger;

    /** @var array */
    private $appendedConfig = [];

    /** @var bool */
    private $hasChanges = false;

    /**
     * @param ConfigBagInterface $configBag
     * @param EntityConfigMerger $entityConfigMerger
     */
    public function __construct(
        ConfigBagInterface $configBag,
        EntityConfigMerger $entityConfigMerger
    ) {
        $this->configBag = $configBag;
        $this->entityConfigMerger = $entityConfigMerger;
    }

    /**
     * {@inheritdoc}
     */
    public function getClassNames(string $version): array
    {
        $result = $this->configBag->getClassNames($version);
        if (!empty($this->appendedConfig['entities'])) {
            $result = array_unique(array_merge($result, array_keys($this->appendedConfig['entities'])));
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig(string $className, string $version): ?array
    {
        $result = $this->configBag->getConfig($className, $version);
        if (!empty($this->appendedConfig['entities'][$className])) {
            $result = $this->entityConfigMerger->merge($this->appendedConfig['entities'][$className], $result);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getRelationConfig(string $className, string $version): ?array
    {
        return $this->configBag->getRelationConfig($className, $version);
    }

    /**
     * @param string $entityClass
     * @param array  $config
     */
    public function appendEntityConfig($entityClass, array $config)
    {
        $this->appendedConfig['entities'][$entityClass] = $config;
        $this->hasChanges = true;
    }

    /**
     * @return bool
     */
    public function restoreConfigs()
    {
        if (!$this->hasChanges) {
            return false;
        }

        $this->appendedConfig = [];
        $this->hasChanges = false;

        return true;
    }
}
