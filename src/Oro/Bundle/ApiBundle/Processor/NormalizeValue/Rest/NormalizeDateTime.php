<?php

namespace Oro\Bundle\ApiBundle\Processor\NormalizeValue\Rest;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\NormalizeValue\NormalizeValueContext;

class NormalizeDateTime implements ProcessorInterface
{
    const REQUIREMENT = '\d{4}(-\d{2}(-\d{2}([T ]\d{2}:\d{2}(:\d{2}(\.\d+)?)?(Z|([-+]\d{2}(:?\d{2})?))?)?)?)?';

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
            if (null !== $value && !$value instanceof \DateTime) {
                // datetime value hack due to the fact that some clients pass + encoded as %20 and not %2B,
                // so it becomes space on symfony side due to parse_str php function in HttpFoundation\Request
                $value = str_replace(' ', '+', $value);
                // The timezone is ignored when DateTime value specifies a timezone (e.g. 2010-01-28T15:00:00+02:00)
                // TODO: should be fixed in BAP-8710. Need to use timezone from system config instead of UTC.
                $context->setResult(new \DateTime($value, new \DateTimeZone('UTC')));
            }
        }
    }
}
