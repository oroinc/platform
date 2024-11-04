<?php

namespace Oro\Bundle\SanitizeBundle\RuleProcessor\Field;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\SanitizeBundle\RuleProcessor\Field\Helper\ProcessorHelper;

/**
 * Date field sanitizing rule processor.
 */
class DateProcessor implements ProcessorInterface
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
        return 'date';
    }

    #[\Override]
    public function getIncompatibilityMessages(
        string $fieldName,
        ClassMetadata $metadata,
        array $sanitizeRuleOptions = []
    ): array {
        if (!$this->helper->isDateField($fieldName, $metadata)) {
            return [sprintf(
                ProcessorHelper::NON_DATE_FIELD_PROCESSED,
                self::getProcessorName(),
                $fieldName,
                $metadata->getName()
            )];
        }

        return [];
    }

    #[\Override]
    public function getSqls(
        string $fieldName,
        ClassMetadata $metadata,
        array $sanitizeRuleOptions = []
    ): array {
        return [sprintf(
            "UPDATE %s SET %s=CURRENT_TIMESTAMP",
            $this->helper->quoteIdentifier($metadata->getTableName()),
            $this->helper->getQuotedColumnName($fieldName, $metadata)
        )];
    }

    protected function doPrepareSerializedFieldUpdate(
        string $fieldName,
        ClassMetadata $metadata,
        array $sanitizeRuleOptions = []
    ): void {
        $this->jsonBuildPairsPostProcessor
            ->addJsonBuildPairForTable($metadata->getTableName(), $fieldName, 'CURRENT_TIMESTAMP(0)');
    }
}
