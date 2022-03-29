<?php

namespace Oro\Bundle\EntityExtendBundle\Provider;

/**
 * Provides form type and form options for the specified field type.
 */
class ExtendFieldFormTypeProvider
{
    private array $typeMap = [];

    public function addExtendTypeMapping(string $fieldType, string $formType, array $formOptions = []): void
    {
        $this->typeMap[$fieldType] = ['type' => $formType, 'options' => $formOptions];
    }

    public function getFormType(string $fieldType): string
    {
        return $this->typeMap[$fieldType]['type'] ?? '';
    }

    public function getFormOptions(string $fieldType): array
    {
        return $this->typeMap[$fieldType]['options'] ?? [];
    }
}
