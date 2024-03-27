<?php

namespace Oro\Bundle\SanitizeBundle\RuleProcessor\Field\Guesser;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\SanitizeBundle\RuleProcessor\Field\EmailProcessor;
use Oro\Bundle\SanitizeBundle\RuleProcessor\Field\Helper\ProcessorHelper;
use Oro\Bundle\SanitizeBundle\RuleProcessor\Field\ProcessorInterface;

/**
 * Email field sanitizing guesser.
 */
class EmailGuesser implements GuesserInterface
{
    public function __construct(private EmailProcessor $processor, private ProcessorHelper $helper)
    {
    }

    public function guessProcessor(string $fieldName, ClassMetadata $metadata): ?ProcessorInterface
    {
        if (!preg_match("/((^[e|E])|E|_e)mail(_|[A-Z0-9]|$)/", $fieldName)) {
            return null;
        }

        return $this->helper->getFieldType($fieldName, $metadata) === 'string' ? $this->processor : null;
    }
}
