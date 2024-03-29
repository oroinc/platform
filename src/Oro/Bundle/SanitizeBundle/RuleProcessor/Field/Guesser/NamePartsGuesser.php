<?php

namespace Oro\Bundle\SanitizeBundle\RuleProcessor\Field\Guesser;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\LocaleBundle\Model\LastNameInterface;
use Oro\Bundle\LocaleBundle\Model\MiddleNameInterface;
use Oro\Bundle\SanitizeBundle\RuleProcessor\Field\Helper\ProcessorHelper;
use Oro\Bundle\SanitizeBundle\RuleProcessor\Field\Md5Processor;
use Oro\Bundle\SanitizeBundle\RuleProcessor\Field\ProcessorInterface;

/**
 * Name parts sanitizing guesser for a field.
 */
class NamePartsGuesser implements GuesserInterface
{
    public function __construct(private Md5Processor $processor, private ProcessorHelper $helper)
    {
    }

    public function guessProcessor(string $fieldName, ClassMetadata $metadata): ?ProcessorInterface
    {
        if ($this->helper->getFieldType($fieldName, $metadata) === 'string'
            && ($fieldName === 'middleName' && is_a($metadata->getName(), MiddleNameInterface::class, true)
            || $fieldName === 'lastName' && is_a($metadata->getName(), LastNameInterface::class, true))
        ) {
            return $this->processor;
        }

        return null;
    }
}
