<?php

namespace Oro\Bundle\DataAuditBundle\Provider;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

class AuditConfigProvider
{
    /** @var ConfigProvider */
    private $configProvider;

    /**
     * @param ConfigProvider $configProvider
     */
    public function __construct(ConfigProvider $configProvider)
    {
        $this->configProvider = $configProvider;
    }

    /**
     * Gets a value that indicates whether the entity is auditable.
     *
     * @param string $entityClass
     *
     * @return bool
     */
    public function isAuditableEntity($entityClass)
    {
        return
            $this->configProvider->hasConfig($entityClass)
            && $this->isAuditable($this->configProvider->getConfig($entityClass));
    }

    /**
     * Gets a value that indicates whether the entity is auditable.
     *
     * @param string $entityClass
     * @param string $fieldName
     *
     * @return bool
     */
    public function isAuditableField($entityClass, $fieldName)
    {
        return
            $this->configProvider->hasConfig($entityClass, $fieldName)
            && $this->isAuditable($this->configProvider->getConfig($entityClass, $fieldName));
    }

    /**
     * Gets the list of all auditable entities.
     *
     * @return string[] An array of class names
     */
    public function getAllAuditableEntities()
    {
        $result = [];
        $configs = $this->configProvider->getConfigs(null, true);
        foreach ($configs as $config) {
            if ($this->isAuditable($config)) {
                $result[] = $config->getId()->getClassName();
            }
        }

        return $result;
    }

    /**
     * @param ConfigInterface $config
     *
     * @return bool
     */
    private function isAuditable(ConfigInterface $config)
    {
        return $config->is('auditable');
    }
}
