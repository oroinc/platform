<?php

namespace Oro\Bundle\SanitizeBundle\RuleProcessor\Field\Guesser;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\SanitizeBundle\RuleProcessor\Field\ProcessorInterface;

/**
 * Compound sanitizing rule guesser.
 */
class CompoundGuesser implements GuesserInterface
{
    public function __construct(private iterable $guessers)
    {
    }

    #[\Override]
    public function guessProcessor(string $fieldName, ClassMetadata $metadata): ?ProcessorInterface
    {
        foreach ($this->guessers as $guesser) {
            $processor = $guesser->guessProcessor($fieldName, $metadata);
            if (null !== $processor) {
                return $processor;
            }
        }

        return null;
    }
}
