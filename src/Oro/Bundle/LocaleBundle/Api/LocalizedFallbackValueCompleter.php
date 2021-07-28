<?php

namespace Oro\Bundle\LocaleBundle\Api;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Processor\GetConfig\CompleteDefinition\CustomDataTypeCompleterInterface;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

/**
 * Completes the configuration of fields with data-type equal to
 * "localizedFallbackValue:{localizedFallbackValueFieldName}".
 * These fields are represented by to-many association to LocalizedFallbackValue.
 * @see \Oro\Bundle\LocaleBundle\Api\Processor\ComputeLocalizedFallbackValues
 */
class LocalizedFallbackValueCompleter implements CustomDataTypeCompleterInterface
{
    public const LOCALIZED_FALLBACK_VALUE_FIELDS = 'localized_fallback_value_fields';

    private const LOCALIZED_FALLBACK_VALUE_PREFIX = 'localizedFallbackValue:';

    /**
     * {@inheritdoc}
     */
    public function completeCustomDataType(
        ClassMetadata $metadata,
        EntityDefinitionConfig $definition,
        string $fieldName,
        EntityDefinitionFieldConfig $field,
        string $dataType,
        string $version,
        RequestType $requestType
    ): bool {
        if (!str_starts_with($dataType, self::LOCALIZED_FALLBACK_VALUE_PREFIX)) {
            return false;
        }

        $localizedFallbackFieldName = substr($dataType, \strlen(self::LOCALIZED_FALLBACK_VALUE_PREFIX));
        $field->setDataType(DataType::STRING);
        $field->setPropertyPath(ConfigUtil::IGNORE_PROPERTY_PATH);
        $field->setDependsOn([$localizedFallbackFieldName]);

        $localizedFallbackField = $definition->findField($localizedFallbackFieldName, true);
        if (null === $localizedFallbackField) {
            if ($localizedFallbackFieldName === $fieldName) {
                throw new \RuntimeException(sprintf(
                    'The circular dependency is detected for localized fallback value field "%1$s::%2$s".'
                    . ' To solve this you can rename the target property of this field. For example:%3$s'
                    . '_%2$s:%3$s    property_path: %2$s%3$s',
                    $metadata->name,
                    $fieldName,
                    "\n"
                ));
            }
            $localizedFallbackField = $definition->addField($localizedFallbackFieldName);
        }
        if (!$localizedFallbackField->hasExcluded()) {
            $localizedFallbackField->setExcluded();
        }
        $localizedFallbackField->getOrCreateTargetEntity()->setMaxResults(-1);

        $fieldNames = $definition->get(self::LOCALIZED_FALLBACK_VALUE_FIELDS) ?? [];
        $fieldNames[] = $fieldName;
        $definition->set(self::LOCALIZED_FALLBACK_VALUE_FIELDS, $fieldNames);

        return true;
    }
}
