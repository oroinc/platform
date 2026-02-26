<?php

namespace Oro\Bundle\SanitizeBundle\RuleProcessor\Field;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\SanitizeBundle\RuleProcessor\Field\Helper\ProcessorHelper;

/**
 * Email field sanitizing rule processor.
 */
class EmailProcessor implements ProcessorInterface
{
    use SerializeFieldCheckerTrait;

    private ?string $customEmailDomain = null;

    public function __construct(
        private JsonBuildPairsPostProcessor $jsonBuildPairsPostProcessor,
        private ProcessorHelper $helper
    ) {
    }

    #[\Override]
    public static function getProcessorName(): string
    {
        return 'email';
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
        $updateSqlValue = $this->getUpdateSqlValue($quotedColumnName, $metadata);

        return [sprintf(
            "UPDATE %s SET %s=$updateSqlValue",
            $this->helper->quoteIdentifier($metadata->getTableName()),
            $quotedColumnName
        )];
    }

    public function setCustomEmailDomain(?string $customEmailDomain = null): void
    {
        $this->customEmailDomain = $customEmailDomain;
    }

    protected function doPrepareSerializedFieldUpdate(
        string $fieldName,
        ClassMetadata $metadata,
        array $sanitizeRuleOptions = []
    ): void {
        $updateSqlValue = $this->getUpdateSqlValue(
            sprintf("serialized_data->>%s", $this->helper->quoteString($fieldName)),
            $metadata
        );

        $this->jsonBuildPairsPostProcessor
            ->addJsonBuildPairForTable($metadata->getTableName(), $fieldName, $updateSqlValue);
    }

    private function getUpdateSqlValue(string $quotedColumnName, ClassMetadata $metadata): string
    {
        // Use MD5 hash of email (first 8 chars) for composite keys, ID for integer keys
        $emailBoxSuffixExpr = sprintf('SUBSTRING(MD5(%s), 1, 8)', $quotedColumnName);
        try {
            $idFieldType = $this->helper->getFieldType($metadata->getSingleIdentifierFieldName(), $metadata);
            if (in_array($idFieldType, ['integer', 'bigint', 'smallint'], true)) {
                $emailBoxSuffixExpr = $metadata->getSingleIdentifierFieldName();
            }
        } catch (\Exception $e) {
            // Composite or missing key - use MD5
        }

        if (!empty($this->customEmailDomain)) {
            $replaced = sprintf(
                'CONCAT(SUBSTRING(%1$s, 1, POSITION(\'@\' IN %1$s)-1), \'_\', %2$s, \'@\', %3$s)',
                $quotedColumnName,
                $emailBoxSuffixExpr,
                $this->helper->quoteString($this->customEmailDomain)
            );
        } else {
            $replaced = sprintf(
                'CONCAT('
                . 'SUBSTRING(%1$s, 1, POSITION(\'@\' IN %1$s)-1), '
                . '\'_\', '
                . '%2$s, '
                . '\'@\', '
                . 'MD5(SUBSTRING(%1$s, POSITION(\'@\' IN %1$s)+1)), '
                . '\'.test\''
                . ')',
                $quotedColumnName,
                $emailBoxSuffixExpr
            );
        }

        return sprintf('CASE WHEN POSITION(\'@\' IN %1$s)>0 THEN %2$s ELSE %1$s END', $quotedColumnName, $replaced);
    }
}
