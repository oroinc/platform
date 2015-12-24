<?php

namespace Oro\Bundle\ApiBundle\Processor\NormalizeValue;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Sets default delimiter that should be used to split a string to separate elements.
 */
class SetArrayDelimiter implements ProcessorInterface
{
    const ARRAY_DELIMITER = ',';

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var NormalizeValueContext $context */

        $arrayDelimiter = $context->getArrayDelimiter();
        if (empty($arrayDelimiter)) {
            $context->setArrayDelimiter(self::ARRAY_DELIMITER);
        }
    }
}
