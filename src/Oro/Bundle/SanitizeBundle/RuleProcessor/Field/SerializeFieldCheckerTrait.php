<?php

namespace Oro\Bundle\SanitizeBundle\RuleProcessor\Field;

use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * The trait includes a wrapper that prepares the serialized field SQL update routine,
 * offers it as a service, and provides validation if the field is not serialized.
 */
trait SerializeFieldCheckerTrait
{
    public function prepareSerializedFieldUpdate(
        string $fieldName,
        ClassMetadata $metadata,
        array $sanitizeRuleOptions = []
    ): void {
        if (!$this->helper->isSerializedField($fieldName, $metadata)) {
            throw new \RuntimeException(sprintf(
                "An attempt to prepare serialized field sanitizing update"
                . " with a non-serialized field '%s' of '%s' entity",
                $fieldName,
                $metadata->getName()
            ));
        }

        $this->doPrepareSerializedFieldUpdate($fieldName, $metadata, $sanitizeRuleOptions);
    }

    abstract protected function doPrepareSerializedFieldUpdate(
        string $fieldName,
        ClassMetadata $metadata,
        array $sanitizeRuleOptions = []
    ): void;
}
