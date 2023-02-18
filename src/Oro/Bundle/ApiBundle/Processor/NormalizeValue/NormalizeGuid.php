<?php

namespace Oro\Bundle\ApiBundle\Processor\NormalizeValue;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Converts a string to GUID/UUID (both are actually synonyms) string (actually a value is kept as is
 * because a string value does not required any transformation).
 * Provides a regular expression that can be used to validate that a string represents a GUID/UUID value.
 */
class NormalizeGuid implements ProcessorInterface
{
    private const REQUIREMENT = '[a-f0-9]{8}\-[a-f0-9]{4}\-[a-f0-9]{4}\-[a-f0-9]{4}\-[a-f0-9]{12}';

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var NormalizeValueContext $context */

        if (!$context->hasRequirement()) {
            $this->processRequirement($context);
        }
        if ($context->hasResult()) {
            $this->processNormalization($context);
        }
    }

    private function processRequirement(NormalizeValueContext $context): void
    {
        $requirement = self::REQUIREMENT;
        if ($context->isArrayAllowed()) {
            $requirement = sprintf('%1$s(%2$s%1$s)*', $requirement, $context->getArrayDelimiter());
        }
        $context->setRequirement($requirement);
    }

    /**
     * Does a value normalization (conversion to a concrete data-type) if needed.
     */
    private function processNormalization(NormalizeValueContext $context): void
    {
        $value = $context->getResult();
        if (\is_string($value)) {
            if ($context->isArrayAllowed()) {
                $context->setResult($this->normalizeArrayValue($value, $context->getArrayDelimiter()));
            } else {
                $this->validateValue($value);
            }
        }
        $context->setProcessed(true);
    }

    private function validateValue(string $value): void
    {
        if (!$this->isValidGuid($value)) {
            throw new \UnexpectedValueException(sprintf('Expected GUID value. Given "%s".', $value));
        }
    }

    /**
     * @param string $value
     * @param string $arrayDelimiter
     *
     * @return string[]|string
     */
    private function normalizeArrayValue(string $value, string $arrayDelimiter): array|string
    {
        $values = explode($arrayDelimiter, $value);
        if (\count($values) === 1) {
            $this->validateValue($value);

            return $value;
        }

        foreach ($values as $val) {
            if (!$this->isValidGuid($val)) {
                throw new \UnexpectedValueException(sprintf('Expected an array of GUIDs. Given "%s".', $value));
            }
        }

        return $values;
    }

    private function isValidGuid(string $value): bool
    {
        return (bool)preg_match('/^' . self::REQUIREMENT . '$/i', $value);
    }
}
