<?php

namespace Oro\Bundle\EntityExtendBundle\Provider;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\EntityBundle\Provider\AbstractExclusionProvider;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

/**
 * The implementation of ExclusionProviderInterface that can be used to ignore
 * "snapshot" field of multi-enum type.
 */
class EnumExclusionProvider extends AbstractExclusionProvider
{
    public function __construct(protected ConfigManager $configManager)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function isIgnoredField(ClassMetadata $metadata, $fieldName)
    {
        if ($this->isMultiEnumField($metadata->name, $fieldName)) {
            return true;
        }

        return false;
    }

    protected function isMultiEnumField(string $className, string $fieldName): bool
    {
        if ($this->configManager->hasConfig($className, $fieldName)) {
            /** @var FieldConfigId $fieldId */
            $fieldId = $this->configManager->getId('extend', $className, $fieldName);
            if (ExtendHelper::isMultiEnumType($fieldId->getFieldType())) {
                return true;
            }
        }

        return false;
    }
}
