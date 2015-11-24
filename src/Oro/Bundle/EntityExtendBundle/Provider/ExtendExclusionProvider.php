<?php

namespace Oro\Bundle\EntityExtendBundle\Provider;

use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Bundle\EntityBundle\Provider\ExclusionProviderInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

/**
 * The implementation of ExclusionProviderInterface that can be used to ignore
 * not accessible and hidden extended entities, fields and relations.
 */
class ExtendExclusionProvider implements ExclusionProviderInterface
{
    /** @var ConfigManager */
    protected $configManager;

    /**
     * @param ConfigManager $configManager
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
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
            || $this->configManager->isHiddenModel($className);
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
            || $this->configManager->isHiddenModel($metadata->name, $fieldName);
    }

    /**
     * {@inheritdoc}
     */
    public function isIgnoredRelation(ClassMetadata $metadata, $associationName)
    {
        if (!$this->configManager->hasConfig($metadata->name, $associationName)) {
            return false;
        }

        $extendFieldConfig = $this->configManager->getFieldConfig('extend', $metadata->name, $associationName);

        return
            !ExtendHelper::isFieldAccessible($extendFieldConfig)
            || $this->configManager->isHiddenModel($metadata->name, $associationName)
            || (
                $extendFieldConfig->has('target_entity')
                && !ExtendHelper::isEntityAccessible(
                    $this->configManager->getEntityConfig('extend', $extendFieldConfig->get('target_entity'))
                )
            );
    }
}
