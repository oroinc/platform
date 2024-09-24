<?php

namespace Oro\Bundle\SanitizeBundle\RuleProcessor\Field\Guesser;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\SanitizeBundle\RuleProcessor\Field\ProcessorInterface;

/**
 * Detour(stub) sanitizing guesser for fields that always returns null.
 * The guesser is used when the functionality for guessing sanitizing rules for fields is disabled.
 */
class AlwaysNullGuesser implements GuesserInterface
{
    #[\Override]
    public function guessProcessor(string $fieldName, ClassMetadata $metadata): ?ProcessorInterface
    {
        return null;
    }
}
