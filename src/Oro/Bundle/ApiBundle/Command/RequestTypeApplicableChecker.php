<?php

namespace Oro\Bundle\ApiBundle\Command;

use Oro\Component\ChainProcessor\ApplicableCheckerInterface;
use Oro\Component\ChainProcessor\ContextInterface;

class RequestTypeApplicableChecker implements ApplicableCheckerInterface
{
    /**
     * {@inheritdoc}
     */
    public function isApplicable(ContextInterface $context, array $processorAttributes)
    {
        $result   = self::ABSTAIN;
        $attrName = 'requestType';
        if (!empty($processorAttributes[$attrName]) && $context->has($attrName)) {
            if (!$this->isMatch($processorAttributes[$attrName], $context->get($attrName))) {
                $result = self::NOT_APPLICABLE;
            }
        }

        return $result;
    }

    /**
     * Checks if a value of a processor attribute matches a corresponding value from the context
     *
     * @param mixed $value
     * @param mixed $contextValue
     *
     * @return bool
     */
    protected function isMatch($value, $contextValue)
    {
        if (is_array($contextValue)) {
            return is_array($value)
                ? count(array_intersect($value, $contextValue)) === count($value)
                : in_array($value, $contextValue, true);
        }

        return $contextValue === $value;
    }
}
