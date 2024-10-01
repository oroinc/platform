<?php

namespace Oro\Bundle\SanitizeBundle\RuleProcessor\Field;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\SanitizeBundle\RuleProcessor\Field\Helper\ProcessorHelper;

/**
 * Set null sanitizing rule processor for a field.
 */
class SetNullProcessor implements ProcessorInterface
{
    use SerializeFieldCheckerTrait;

    public function __construct(
        private JsonBuildPairsPostProcessor $jsonBuildPairsPostProcessor,
        private ProcessorHelper $helper
    ) {
    }

    #[\Override]
    public static function getProcessorName(): string
    {
        return 'set_null';
    }

    #[\Override]
    public function getIncompatibilityMessages(
        string $fieldName,
        ClassMetadata $metadata,
        array $sanitizeRuleOptions = []
    ): array {
        return [];
    }

    #[\Override]
    public function getSqls(
        string $fieldName,
        ClassMetadata $metadata,
        array $sanitizeRuleOptions = []
    ): array {
        return [sprintf(
            "UPDATE %s SET %s=%s",
            $this->helper->quoteIdentifier($metadata->getTableName()),
            $this->helper->getQuotedColumnName($fieldName, $metadata),
            $this->getNullValue($this->helper->getFieldType($fieldName, $metadata))
        )];
    }

    protected function doPrepareSerializedFieldUpdate(
        string $fieldName,
        ClassMetadata $metadata,
        array $sanitizeRuleOptions = []
    ): void {
        $nullValue = $this->getNullValue($this->helper->getFieldType($fieldName, $metadata));
        $this->jsonBuildPairsPostProcessor
            ->addJsonBuildPairForTable($metadata->getTableName(), $fieldName, $nullValue);
    }

    private function getNullValue(string $fieldType): string
    {
        switch ($fieldType) {
            case 'array':
                return "encode('" . serialize([]) . "'::bytea, 'base64')";
            case 'object':
                return "encode('" . serialize(null) . "'::bytea, 'base64')";
            case 'json':
                return "jsonb_build_array()";
            default:
                return 'NULL';
        }
    }
}
