<?php

namespace Oro\Bundle\DataAuditBundle\Model;

use Oro\Bundle\ConfigBundle\Config\ConfigBag;
use Oro\Bundle\DataAuditBundle\Provider\SensitiveConfigFieldProvider;

/**
 * Turns the old and the new value of a changed configuration setting into what the audit stores: the
 * Data Audit field type plus the two values coerced to it.
 *
 * The type comes from the setting's own ``data_type`` mapped through the canonical
 * {@see AuditFieldTypeRegistry} (a boolean stays a boolean, integer/decimal/array keep their type), so
 * every value lands in its typed column; anything unsupported becomes readable, searchable text.
 *
 * A secret setting is the exception: its value is replaced with a placeholder, so the audit shows that
 * the setting changed without disclosing what it changed to.
 */
class ConfigAuditValueNormalizer
{
    /** Shown instead of a secret value, mirroring how the configuration form masks such a setting. */
    private const string MASKED_VALUE = '***';

    public function __construct(
        private readonly ConfigBag $configBag,
        private readonly SensitiveConfigFieldProvider $sensitiveFieldProvider
    ) {
    }

    /**
     * @return array{type: string, old: mixed, new: mixed}
     */
    public function normalize(string $configKey, mixed $old, mixed $new): array
    {
        if ($this->sensitiveFieldProvider->isSensitive($configKey)) {
            return [
                'type' => AuditFieldTypeRegistry::TYPE_TEXT,
                'old' => $this->maskValue($old),
                'new' => $this->maskValue($new),
            ];
        }

        $type = $this->resolveDataType($configKey);

        return [
            'type' => $type,
            'old' => $this->castValue($old, $type),
            'new' => $this->castValue($new, $type),
        ];
    }

    private function maskValue(mixed $value): ?string
    {
        return null === $value || '' === $value ? null : self::MASKED_VALUE;
    }

    private function resolveDataType(string $configKey): string
    {
        $field = $this->configBag->getFieldsRoot($configKey);
        $dataType = \is_array($field) ? ($field['data_type'] ?? null) : null;

        return $dataType && AuditFieldTypeRegistry::hasType($dataType)
            ? AuditFieldTypeRegistry::getAuditType($dataType)
            : AuditFieldTypeRegistry::TYPE_TEXT;
    }

    private function castValue(mixed $value, string $type): mixed
    {
        if (null === $value) {
            return null;
        }

        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int)$value,
            'float' => (float)$value,
            'array' => \is_array($value) ? ($value ?: null) : (array)$value,
            default => $this->formatValue($value),
        };
    }

    /**
     * Renders a value as text for the audit Data column: scalars directly, a DateTime formatted, a flat
     * list of scalars (e.g. a multiselect) comma-separated, anything else as JSON. An empty array means
     * "no value" (null) so that it is not rendered as "[]".
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function formatValue(mixed $value): ?string
    {
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        if (is_scalar($value)) {
            return (string)$value;
        }
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }
        if (is_array($value)) {
            if ([] === $value) {
                return null;
            }
            if (array_is_list($value) && array_all($value, static fn ($item): bool => is_scalar($item))) {
                return implode(', ', array_map(
                    static fn ($item): string => is_bool($item) ? ($item ? '1' : '0') : (string)$item,
                    $value
                ));
            }
        }

        $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return false === $encoded ? null : $encoded;
    }
}
