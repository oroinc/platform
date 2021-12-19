<?php

namespace Oro\Bundle\EntityExtendBundle\Provider;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\EntityBundle\Provider\ExclusionProviderInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

/**
 * The implementation of ExclusionProviderInterface that can be used to ignore
 * not accessible and hidden extended entities, fields and relations.
 */
class ExtendExclusionProvider implements ExclusionProviderInterface
{
    /** @var ConfigManager */
    protected $configManager;

    /** @var bool */
    protected $excludeHiddenEntities;

    /** @var bool */
    protected $excludeHiddenFields;

    /**
     * @param ConfigManager $configManager
     * @param bool          $excludeHiddenEntities
     * @param bool          $excludeHiddenFields
     */
    public function __construct(
        ConfigManager $configManager,
        $excludeHiddenEntities = false,
        $excludeHiddenFields = false
    ) {
        $this->configManager         = $configManager;
        $this->excludeHiddenEntities = $excludeHiddenEntities;
        $this->excludeHiddenFields   = $excludeHiddenFields;
    }

    /**
     * {@inheritdoc}
     */
    public function isIgnoredEntity($className)
    {
        if (!$this->configManager->hasConfig($className)) {
            return false;
        }

        $extendConfig = $this->configManager->getEntityConfig('extend', $className);

        return
            !ExtendHelper::isEntityAccessible($extendConfig)
            || ($this->excludeHiddenEntities && $this->configManager->isHiddenModel($className));
    }

    /**
     * {@inheritdoc}
     */
    public function isIgnoredField(ClassMetadata $metadata, $fieldName)
    {
        if (!$this->configManager->hasConfig($metadata->name, $fieldName)) {
            return false;
        }

        $extendFieldConfig = $this->configManager->getFieldConfig('extend', $metadata->name, $fieldName);

        return
            !ExtendHelper::isFieldAccessible($extendFieldConfig)
            || ($this->excludeHiddenFields && $this->configManager->isHiddenModel($metadata->name, $fieldName));
    }

    /**
     * {@inheritdoc}
     */
    public function isIgnoredRelation(ClassMetadata $metadata, $associationName)
    {
        if (!$this->configManager->hasConfig($metadata->name, $associationName)) {
            // check for default field of oneToMany or manyToMany relation
            if (str_starts_with($associationName, ExtendConfigDumper::DEFAULT_PREFIX)) {
                $guessedName = substr($associationName, \strlen(ExtendConfigDumper::DEFAULT_PREFIX));
                if (!empty($guessedName) && $this->configManager->hasConfig($metadata->name, $guessedName)) {
                    return $this->isIgnoredExtendRelation($metadata->name, $guessedName);
                }
            }

            return false;
        }

        return $this->isIgnoredExtendRelation($metadata->name, $associationName);
    }

    private function isIgnoredExtendRelation(string $entityClass, string $associationName): bool
    {
        $extendFieldConfig = $this->configManager->getFieldConfig('extend', $entityClass, $associationName);
        if (!ExtendHelper::isFieldAccessible($extendFieldConfig)) {
            return true;
        }
        if ($this->excludeHiddenFields && $this->configManager->isHiddenModel($entityClass, $associationName)) {
            return true;
        }
        if ($extendFieldConfig->has('target_entity')) {
            $targetEntity = $extendFieldConfig->get('target_entity');
            if (!ExtendHelper::isEntityAccessible($this->configManager->getEntityConfig('extend', $targetEntity))) {
                return true;
            }
            if ($this->excludeHiddenEntities && $this->configManager->isHiddenModel($targetEntity)) {
                return true;
            }
        }

        return false;
    }
}
