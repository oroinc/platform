<?php

namespace Oro\Bundle\SanitizeBundle\RuleProcessor\Field;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\SanitizeBundle\RuleProcessor\Field\Helper\ProcessorHelper;

/**
 * Attachment content sanitizing rule processor for a field.
 */
class AttachmentProcessor implements ProcessorInterface
{
    use SerializeFieldCheckerTrait;

    private ?string $blankFileContent = null;

    public function __construct(
        private JsonBuildPairsPostProcessor $jsonBuildPairsPostProcessor,
        private ProcessorHelper $helper
    ) {
    }

    public static function getProcessorName(): string
    {
        return 'attachment';
    }

    public function getIncompatibilityMessages(
        string $fieldName,
        ClassMetadata $metadata,
        array $sanitizeRuleOptions = []
    ): array {
        if (!$this->helper->isTextField($fieldName, $metadata)) {
            return [sprintf(
                ProcessorHelper::NON_LONGTEXT_FIELD_PROCESSED,
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
        return [sprintf(
            "UPDATE %s SET %s='%s'",
            $this->helper->quoteIdentifier($metadata->getTableName()),
            $this->helper->getQuotedColumnName($fieldName, $metadata),
            $this->getBlankFileContent()
        )];
    }

    protected function doPrepareSerializedFieldUpdate(
        string $fieldName,
        ClassMetadata $metadata,
        array $sanitizeRuleOptions = []
    ): void {
        $blankFileContent = $this->getBlankFileContent();
        $this->jsonBuildPairsPostProcessor
            ->addJsonBuildPairForTable($metadata->getTableName(), $fieldName, sprintf("'%s'", $blankFileContent));
    }

    private function getBlankFileContent(): string
    {
        if (null === $this->blankFileContent) {
            $this->blankFileContent = base64_encode(file_get_contents(
                __DIR__ . DIRECTORY_SEPARATOR . 'fixture' . DIRECTORY_SEPARATOR . 'blank.png'
            ));
        }

        return $this->blankFileContent;
    }
}
