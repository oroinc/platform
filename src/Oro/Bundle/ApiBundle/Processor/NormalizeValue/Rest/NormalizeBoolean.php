<?php

namespace Oro\Bundle\ApiBundle\Processor\NormalizeValue\Rest;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\NormalizeValue\NormalizeValueContext;

class NormalizeBoolean implements ProcessorInterface
{
    const REQUIREMENT = '0|1|true|false|yes|no';

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var NormalizeValueContext $context */

        if (!$context->hasRequirement()) {
            $context->setRequirement(self::REQUIREMENT);
        }
        if ($context->hasResult()) {
            $value = $context->getResult();
            if (null !== $value && is_string($value)) {
                switch ($value) {
                    case '1':
                    case 'true':
                    case 'yes':
                        $normalizedValue = true;
                        break;
                    case '0':
                    case 'false':
                    case 'no':
                        $normalizedValue = false;
                        break;
                    default:
                        throw new \RuntimeException(
                            sprintf('Expected boolean value. Given "%s".', $value)
                        );
                }
                $context->setResult($normalizedValue);
            }
        }
    }
}
