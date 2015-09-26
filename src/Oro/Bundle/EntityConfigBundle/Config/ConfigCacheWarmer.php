<?php

namespace Oro\Bundle\EntityConfigBundle\Config;

use Doctrine\ORM\Mapping\ClassMetadata;

class ConfigCacheWarmer
{
    /** @var ConfigManager */
    protected $configManager;

    /** @var ConfigCache */
    protected $cache;

    /** @var EntityManagerBag */
    protected $entityManagerBag;

    /**
     * @param ConfigManager    $configManager
     * @param ConfigCache      $cache
     * @param EntityManagerBag $entityManagerBag
     */
    public function __construct(ConfigManager $configManager, ConfigCache $cache, EntityManagerBag $entityManagerBag)
    {
        $this->configManager    = $configManager;
        $this->cache            = $cache;
        $this->entityManagerBag = $entityManagerBag;
    }

    /**
     * Warms up the configuration data cache.
     */
    public function warmUpCache()
    {
        foreach ($this->configManager->getProviders() as $scope => $provider) {
            foreach ($this->configManager->getConfigs($scope, null, true) as $config) {
                $this->configManager->getConfigs($scope, $config->getId()->getClassName(), true);
            }
        }

        $cached = $this->cache->getEntities();
        foreach ($this->entityManagerBag->getEntityManagers() as $em) {
            /** @var ClassMetadata $metadata */
            foreach ($em->getMetadataFactory()->getAllMetadata() as $metadata) {
                if ($metadata->isMappedSuperclass) {
                    continue;
                }

                $className = $metadata->getName();
                if (!isset($cached[$className]) && null === $this->cache->getConfigurable($className)) {
                    $this->cache->saveConfigurable(false, $className);
                    foreach ($metadata->getFieldNames() as $fieldName) {
                        $this->cache->saveConfigurable(false, $className, $fieldName);
                    }
                    foreach ($metadata->getAssociationNames() as $fieldName) {
                        $this->cache->saveConfigurable(false, $className, $fieldName);
                    }
                }
            }
        }
    }
}
