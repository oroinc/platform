<?php

namespace Oro\Bundle\SanitizeBundle\RuleProcessor\Field\Guesser;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\SanitizeBundle\RuleProcessor\Field\ProcessorInterface;

/**
 * Field sanitizing rule guesser interface.
 */
interface GuesserInterface
{
    public function guessProcessor(string $fieldName, ClassMetadata $metadata): ?ProcessorInterface;
}
