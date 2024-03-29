<?php

namespace Oro\Bundle\SanitizeBundle\RuleProcessor\Field;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\SanitizeBundle\RuleProcessor\Field\Helper\ProcessorHelper;

/**
 * Processor that applies a rule for sanitizing and masking digits in a particular field.
 */
class DigitsMaskProcessor implements ProcessorInterface
{
    use SerializeFieldCheckerTrait;

    public function __construct(
        private JsonBuildPairsPostProcessor $jsonBuildPairsPostProcessor,
        private ProcessorHelper $helper,
        private string $defaultMask = 'XXXXX'
    ) {
    }

    public static function getProcessorName(): string
    {
        return 'digits_mask';
    }

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

    /**
     * {@inheritdoc}
     */
    public function getSqls(
        string $fieldName,
        ClassMetadata $metadata,
        array $sanitizeRuleOptions = []
    ): array {
        $quotedColumnName = $this->helper->getQuotedColumnName($fieldName, $metadata);
        return [sprintf(
            "UPDATE %s SET %s=%s",
            $this->helper->quoteIdentifier($metadata->getTableName()),
            $quotedColumnName,
            $this->getUpdateSqlValue($quotedColumnName, $sanitizeRuleOptions)
        )];
    }

    protected function doPrepareSerializedFieldUpdate(
        string $fieldName,
        ClassMetadata $metadata,
        array $sanitizeRuleOptions = []
    ): void {
        $updateSqlValue = $this->getUpdateSqlValue(
            sprintf("serialized_data->>%s", $this->helper->quoteString($fieldName)),
            $sanitizeRuleOptions
        );
        $this->jsonBuildPairsPostProcessor
            ->addJsonBuildPairForTable($metadata->getTableName(), $fieldName, $updateSqlValue);
    }

    private function getUpdateSqlValue(string $quotedColumnName, array $sanitizeRuleOptions): string
    {
        $mask = !empty($sanitizeRuleOptions['mask']) ? $sanitizeRuleOptions['mask'] : $this->defaultMask;
        $maskLength = 0;
        $fmExpression = preg_replace_callback(
            '/(?<!\\\\)X+/',
            function ($matches) use (&$maskLength) {
                $maskLength += strlen($matches[0]);
                return sprintf('"%s"', str_pad('', strlen($matches[0]), '0'));
            },
            str_replace(['\\', '"'], ['\\\\', '\\"'], $mask)
        );
        $fmExpression = str_replace('\\\\X', 'X', $fmExpression);

        return sprintf(
            'CASE WHEN %s IS NOT NULL THEN to_char(random() * %d, %s) END',
            $quotedColumnName,
            pow(10, $maskLength),
            $this->helper->quoteString('FM"' . $fmExpression)
        );
    }
}
