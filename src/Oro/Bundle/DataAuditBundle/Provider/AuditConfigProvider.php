<?php

namespace Oro\Bundle\DataAuditBundle\Provider;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;

/**
 * Checks whatever field or entity is auditable depending on EntityConfig
 */
class AuditConfigProvider
{
    const DATA_AUDIT_SCOPE = 'dataaudit';

    /** @var ConfigManager */
    private $configManager;

    /**
     * @param ConfigManager $configManager
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
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
        if (\is_a($entityClass, AbstractEnumValue::class, true)) {
            return true;
        }

        return
            $this->configManager->hasConfig($entityClass)
            && $this->isAuditable(
                $this->configManager->getEntityConfig(self::DATA_AUDIT_SCOPE, $entityClass)
            );
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
            $this->configManager->hasConfig($entityClass, $fieldName)
            && $this->isAuditable(
                $this->configManager->getFieldConfig(self::DATA_AUDIT_SCOPE, $entityClass, $fieldName)
            );
    }

    /**
     * Gets the list of all auditable entities.
     *
     * @return string[] An array of class names
     */
    public function getAllAuditableEntities()
    {
        $result = [];
        $configs = $this->configManager->getConfigs(self::DATA_AUDIT_SCOPE, null, true);
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
