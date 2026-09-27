<?php

namespace Oro\Bundle\DataAuditBundle\Model;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\LocaleBundle\Entity\AbstractLocalizedFallbackValue;

/**
 * Turns the old and the new value of a changed menu item property into what the audit stores
 */
class MenuAuditValueNormalizer
{
    public function __construct(
        private readonly EntityNameResolver $entityNameResolver
    ) {
    }

    /**
     * @return array{type: string, old: mixed, new: mixed}
     */
    public function normalize(mixed $old, mixed $new): array
    {
        $type = $this->resolveType($new ?? $old);

        return [
            'type' => $type,
            'old' => $this->castValue($old, $type),
            'new' => $this->castValue($new, $type),
        ];
    }

    public function normalizeValue(mixed $value): mixed
    {
        return $this->castValue($value, $this->resolveType($value));
    }

    public function isSame(mixed $old, mixed $new): bool
    {
        if ($old === $new) {
            return true;
        }

        $change = $this->normalize($old, $new);

        return $change['old'] === $change['new'];
    }

    private function resolveType(mixed $value): string
    {
        return match (true) {
            \is_bool($value) => 'boolean',
            \is_int($value) => 'integer',
            \is_float($value) => 'float',
            default => AuditFieldTypeRegistry::TYPE_TEXT,
        };
    }

    private function castValue(mixed $value, string $type): mixed
    {
        if (null === $value || '' === $value || [] === $value) {
            return null;
        }

        return match ($type) {
            'boolean' => (bool)$value,
            'integer' => (int)$value,
            'float' => (float)$value,
            default => $this->formatValue($value),
        };
    }

    private function formatValue(mixed $value): ?string
    {
        $value = $this->unwrapLocalizedValue($value);

        return match (true) {
            null === $value, '' === $value => null,
            \is_bool($value) => $value ? '1' : '0',
            \is_array($value) => $this->formatList($value),
            $value instanceof \DateTimeInterface => $value->format('Y-m-d H:i:s'),
            $value instanceof File => $this->formatFile($value),
            \is_object($value) => (string)$this->entityNameResolver->getName($value) ?: null,
            default => (string)$value,
        };
    }

    private function unwrapLocalizedValue(mixed $value): mixed
    {
        return match (true) {
            $value instanceof AbstractLocalizedFallbackValue => $value->getString() ?? $value->getText(),
            $value instanceof Collection => $this->getDefaultLocalizedValue($value),
            default => $value,
        };
    }

    private function formatList(array $value): ?string
    {
        $items = [];
        foreach ($value as $item) {
            $item = $this->formatValue($item);
            if (null !== $item) {
                $items[] = $item;
            }
        }

        return $items ? implode(', ', $items) : null;
    }

    private function formatFile(File $file): ?string
    {
        return $file->getOriginalFilename() ?: $file->getFilename() ?: null;
    }

    private function getDefaultLocalizedValue(Collection $values): ?string
    {
        foreach ($values as $value) {
            if ($value instanceof AbstractLocalizedFallbackValue && null === $value->getLocalization()) {
                return $value->getString() ?? $value->getText();
            }
        }

        return null;
    }
}
