<?php

namespace Oro\Bundle\SanitizeBundle\RuleProcessor\Field\Helper;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\EntityExtend\ExtendEntityMetadataProvider;

/**
 * Helper with common routines for a field sanitizing rule processor.
 * It also includes routines such as field name and length retrieval
 * that can be applied to a field's rule processor guesser.
 */
class ProcessorHelper
{
    public const NON_STRING_FIELD_PROCESSED
        = "The specified sanitizing rule '%s' cannot be applied to the non-string field '%s' of the '%s' entity";
    public const NON_LONGTEXT_FIELD_PROCESSED
        = "The specified sanitizing rule '%s' cannot be applied to the non-long text field '%s' of the '%s' entity";
    public const NON_DATE_FIELD_PROCESSED
        = "The specified sanitizing rule '%s' cannot be applied to the non-date field '%s' of the '%s' entity";

    private const EXTEND_CONFIG_SCOPE = 'extend';

    public function __construct(
        private ConfigManager $configManager,
        private ExtendEntityMetadataProvider $extendEntityMetadataProvider
    ) {
    }

    public function isSerializedField(string $fieldName, ClassMetadata $metadata): bool
    {
        $extendMetadata = $this->extendEntityMetadataProvider->getExtendEntityFieldsMetadata($metadata->getName());
        return !empty($extendMetadata[$fieldName][ExtendEntityMetadataProvider::IS_SERIALIZED]);
    }

    public function getFieldType(string $fieldName, ClassMetadata $metadata): string
    {
        if ($this->isSerializedField($fieldName, $metadata)) {
            $extendMetadata
                = $this->extendEntityMetadataProvider->getExtendEntityFieldsMetadata($metadata->getName());
            return $extendMetadata[$fieldName][ExtendEntityMetadataProvider::FIELD_TYPE];
        } else {
            return $metadata->getFieldMapping($fieldName)['type'];
        }
    }

    public function getFieldLength(string $fieldName, ClassMetadata $metadata): int
    {
        if ($this->isSerializedField($fieldName, $metadata)) {
            $fieldExtendConfig =
                $this->configManager->getFieldConfig(self::EXTEND_CONFIG_SCOPE, $metadata->getName(), $fieldName);

            return (int) $fieldExtendConfig->get('length');
        } else {
            $fieldMapping = $metadata->getFieldMapping($fieldName);

            return (int) ($fieldMapping['length'] ?? 0);
        }
    }

    public function getQuotedColumnName(string $fieldName, ClassMetadata $metadata): string
    {
        return $this->quoteIdentifier(strtolower($metadata->getColumnName($fieldName)));
    }

    public function isStringField(string $fieldName, ClassMetadata $metadata): bool
    {
        $types = ['string', 'text', 'crypted_string'];

        return in_array($this->getFieldType($fieldName, $metadata), $types, true);
    }

    public function isTextField(string $fieldName, ClassMetadata $metadata): bool
    {
        return $this->getFieldType($fieldName, $metadata) === 'text';
    }

    public function isNumericField(string $fieldName, ClassMetadata $metadata): bool
    {
        $types = ['decimal', 'smallint', 'integer', 'bigint'];

        return in_array($this->getFieldType($fieldName, $metadata), $types, true);
    }

    public function isDateField(string $fieldName, ClassMetadata $metadata): bool
    {
        $types = ['datetime', 'date', 'datetime_immutable', 'date_immutable'];

        return in_array($this->getFieldType($fieldName, $metadata), $types, true);
    }

    public function quoteIdentifier(string $value): string
    {
        return $this->configManager
            ->getEntityManager()
            ->getConnection()
            ->quoteIdentifier($value);
    }

    public function quoteString(string $value): string
    {
        return $this->configManager
            ->getEntityManager()
            ->getConnection()
            ->quote($value);
    }
}
