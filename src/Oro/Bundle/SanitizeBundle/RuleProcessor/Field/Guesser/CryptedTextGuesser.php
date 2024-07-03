<?php

namespace Oro\Bundle\SanitizeBundle\RuleProcessor\Field\Guesser;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\SanitizeBundle\RuleProcessor\Field\Helper\ProcessorHelper;
use Oro\Bundle\SanitizeBundle\RuleProcessor\Field\Md5Processor;
use Oro\Bundle\SanitizeBundle\RuleProcessor\Field\ProcessorInterface;

/**
 * Crypted text field sanitizing guesser.
 */
class CryptedTextGuesser implements GuesserInterface
{
    public function __construct(private Md5Processor $processor, private ProcessorHelper $helper)
    {
    }

    public function guessProcessor(string $fieldName, ClassMetadata $metadata): ?ProcessorInterface
    {
        return $this->helper->getFieldType($fieldName, $metadata) === 'crypted_text' ? $this->processor : null;
    }
}
