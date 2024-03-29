<?php

namespace Oro\Bundle\SanitizeBundle\RuleProcessor\Field;

use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * Wrapper for another field sanitizing rule processor. It is recommended to set default options
 * for a wrapped processor using a dependency injection setup. This allows the new processor
 * to appoint only the processor name for a field.
 */
class WrappedProcessor implements ProcessorInterface
{
    private ProcessorInterface $innerProcessor;

    private array $options = [];

    public function __construct(ProcessorInterface $innerProcessor)
    {
        $this->innerProcessor = $innerProcessor;
    }

    public function setOptions(array $options): void
    {
        $this->options = $options;
    }

    public static function getProcessorName(): string
    {
        throw new \RuntimeException(
            "Service of 'wrapped' field proccessor must be defined with 'processor_name' property of the tag."
        );
    }

    public function getIncompatibilityMessages(
        string $fieldName,
        ClassMetadata $metadata,
        array $sanitizeRuleOptions = []
    ): array {
        $sanitizeRuleOptions = array_merge($this->options, $sanitizeRuleOptions);

        return $this->innerProcessor->getIncompatibilityMessages($fieldName, $metadata, $sanitizeRuleOptions);
    }

    public function prepareSerializedFieldUpdate(
        string $fieldName,
        ClassMetadata $metadata,
        array $sanitizeRuleOptions = []
    ): void {
        $sanitizeRuleOptions = array_merge($this->options, $sanitizeRuleOptions);

        $this->innerProcessor->prepareSerializedFieldUpdate($fieldName, $metadata, $sanitizeRuleOptions);
    }

    public function getSqls(
        string $fieldName,
        ClassMetadata $metadata,
        array $sanitizeRuleOptions = []
    ): array {
        $sanitizeRuleOptions = array_merge($this->options, $sanitizeRuleOptions);

        return $this->innerProcessor->getSqls($fieldName, $metadata, $sanitizeRuleOptions);
    }
}
