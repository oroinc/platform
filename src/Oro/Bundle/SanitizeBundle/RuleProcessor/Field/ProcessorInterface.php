<?php

namespace Oro\Bundle\SanitizeBundle\RuleProcessor\Field;

use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * Field sanitizing rule processor interface.
 */
interface ProcessorInterface
{
    public static function getProcessorName(): string;

    public function getIncompatibilityMessages(
        string $fieldName,
        ClassMetadata $metadata,
        array $sanitizeRuleOptions = []
    ): array;

    public function prepareSerializedFieldUpdate(
        string $fieldName,
        ClassMetadata $metadata,
        array $sanitizeRuleOptions = []
    ): void;

    /**
     * return string[]
     */
    public function getSqls(string $fieldName, ClassMetadata $metadata, array $sanitizeRuleOptions = []): array;
}
