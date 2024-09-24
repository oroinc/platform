<?php

namespace Oro\Bundle\SanitizeBundle\RuleProcessor\Field;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\SanitizeBundle\RuleProcessor\Field\Helper\ProcessorHelper;

/**
 * MD5 sanitizing rule processor for a field.
 */
class Md5Processor implements ProcessorInterface
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
        return 'md5';
    }

    #[\Override]
    public function getIncompatibilityMessages(
        string $fieldName,
        ClassMetadata $metadata,
        array $sanitizeRuleOptions = []
    ): array {
        if (!$this->helper->isStringField($fieldName, $metadata)) {
            return [sprintf(
                ProcessorHelper::NON_STRING_FIELD_PROCESSED,
                self::getProcessorName(),
                $fieldName,
                $metadata->getName()
            )];
        }

        return [];
    }

    #[\Override]
    public function getSqls(string $fieldName, ClassMetadata $metadata, array $sanitizeRuleOptions = []): array
    {
        $quotedColumnName = $this->helper->getQuotedColumnName($fieldName, $metadata);
        $columnLength =
            (int) ($sanitizeRuleOptions['length'] ?? $this->helper->getFieldLength($fieldName, $metadata));

        $updateSqlValue = $columnLength > 0
            ? $this->substring($this->md5($quotedColumnName), $columnLength)
            : $this->md5($quotedColumnName);

        return [sprintf(
            "UPDATE %s SET %s=$updateSqlValue",
            $this->helper->quoteIdentifier($metadata->getTableName()),
            $quotedColumnName
        )];
    }

    protected function doPrepareSerializedFieldUpdate(
        string $fieldName,
        ClassMetadata $metadata,
        array $sanitizeRuleOptions = []
    ): void {
        $quotedFieldName = sprintf("serialized_data->>'%s'", $this->helper->quoteString($fieldName));
        $fieldLength = (int) ($sanitizeRuleOptions['length' ] ?? 0);
        $fieldLength = $fieldLength ?: $this->helper->getFieldLength($fieldName, $metadata);

        $updateSqlValue = $fieldLength > 0
            ? $this->substring($this->md5($quotedFieldName), $fieldLength)
            : $this->md5($quotedFieldName);

        $this->jsonBuildPairsPostProcessor
            ->addJsonBuildPairForTable($metadata->getTableName(), $fieldName, $updateSqlValue);
    }

    private function md5(string $quotedColumnName)
    {
        return sprintf('MD5(%s || RANDOM()::TEXT)', $quotedColumnName);
    }

    private function substring(string $string, int $length)
    {
        return sprintf('SUBSTRING(%s FROM 1 FOR %s)', $string, $length);
    }
}
