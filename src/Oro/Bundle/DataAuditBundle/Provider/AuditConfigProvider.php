<?php

namespace Oro\Bundle\DataAuditBundle\Provider;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;

/**
 * Provider for wide-used audit configuration
 * Checks whatever field or entity is auditable depending on EntityConfig
 */
class AuditConfigProvider
{
    const DATA_AUDIT_SCOPE = 'dataaudit';

    /** @var ConfigManager */
    private $configManager;

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
        if (is_a($entityClass, AbstractEnumValue::class, true)) {
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
        if (!$this->isAuditableEntity($entityClass) || !$this->configManager->hasConfig($entityClass, $fieldName)) {
            return false;
        }

        if (is_a($entityClass, AbstractEnumValue::class, true)) {
            return true;
        }

        $config = $this->configManager->getFieldConfig(self::DATA_AUDIT_SCOPE, $entityClass, $fieldName);

        return $this->isAuditable($config);
    }

    /**
     * Gets a value that indicates whether the entity`s log must be added to audit of related auditable entity.
     *
     * @param string $entityClass
     * @param string $fieldName
     *
     * @return bool
     */
    public function isPropagateField($entityClass, $fieldName)
    {
        if (!$this->isAuditableEntity($entityClass) || !$this->isAuditableField($entityClass, $fieldName)) {
            return false;
        }

        $config = $this->configManager->getFieldConfig(self::DATA_AUDIT_SCOPE, $entityClass, $fieldName);

        return $config->is('propagate');
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

    public function getAuditableFields(string $entityClass): array
    {
        $result = [];
        $configs = $this->configManager->getConfigs(self::DATA_AUDIT_SCOPE, $entityClass, false);
        foreach ($configs as $config) {
            $configId = $config->getId();
            if ($this->isAuditable($config) && $configId instanceof FieldConfigId) {
                $result[] = $configId->getFieldName();
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
