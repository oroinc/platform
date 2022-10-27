<?php

namespace Oro\Bundle\ApiBundle\Command\Util;

use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\MatchApplicableChecker;

/**
 * This applicable checker allows to check whether a "requestType" attribute
 * is matched to the "requestType" option in the context.
 * The "requestType" option in the context should be an instance of
 * {@see \Oro\Bundle\ApiBundle\Request\RequestType} class.
 */
class RequestTypeApplicableChecker extends MatchApplicableChecker
{
    private const REQUEST_TYPE = 'requestType';

    /**
     * {@inheritdoc}
     */
    public function isApplicable(ContextInterface $context, array $processorAttributes)
    {
        $result = self::ABSTAIN;
        if (!empty($processorAttributes[self::REQUEST_TYPE]) && $context->has(self::REQUEST_TYPE)) {
            /** @var RequestType $requestType */
            $requestType = $context->get(self::REQUEST_TYPE);
            if (!$requestType->isEmpty()
                && !$this->isMatch($processorAttributes[self::REQUEST_TYPE], $requestType, self::REQUEST_TYPE)
            ) {
                $result = self::NOT_APPLICABLE;
            }
        }

        return $result;
    }
}
