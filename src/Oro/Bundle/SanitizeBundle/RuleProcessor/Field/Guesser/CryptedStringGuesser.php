<?php

namespace Oro\Bundle\SanitizeBundle\RuleProcessor\Field\Guesser;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\SanitizeBundle\RuleProcessor\Field\Helper\ProcessorHelper;
use Oro\Bundle\SanitizeBundle\RuleProcessor\Field\Md5Processor;
use Oro\Bundle\SanitizeBundle\RuleProcessor\Field\ProcessorInterface;

/**
 * Crypted string field sanitizing guesser.
 */
class CryptedStringGuesser implements GuesserInterface
{
    public function __construct(private Md5Processor $processor, private ProcessorHelper $helper)
    {
    }

    public function guessProcessor(string $fieldName, ClassMetadata $metadata): ?ProcessorInterface
    {
        return $this->helper->getFieldType($fieldName, $metadata) === 'crypted_string' ? $this->processor : null;
    }
}
