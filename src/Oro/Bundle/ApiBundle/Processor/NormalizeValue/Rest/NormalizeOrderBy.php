<?php

namespace Oro\Bundle\ApiBundle\Processor\NormalizeValue\Rest;

use Doctrine\Common\Collections\Criteria;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\NormalizeValue\NormalizeValueContext;

/**
 * Normalizes a value of "orderBy" type for REST requests.
 * Expected format of a string value: field1,-field2,...
 * The "-" is used as shortcut for DESC.
 */
class NormalizeOrderBy implements ProcessorInterface
{
    const REQUIREMENT = '-?[\w\.]+(,-?[\w\.]+)*';

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
                $orderBy = [];
                $items   = explode(',', $value);
                foreach ($items as $item) {
                    $item = trim($item);
                    if (0 === strpos($item, '-')) {
                        $orderBy[substr($item, 1)] = Criteria::DESC;
                    } else {
                        $orderBy[$item] = Criteria::ASC;
                    }
                }
                $context->setResult($orderBy);
            }
        }
    }
}
