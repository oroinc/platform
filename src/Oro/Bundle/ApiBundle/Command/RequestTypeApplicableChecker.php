<?php

namespace Oro\Bundle\ApiBundle\Command;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\MatchApplicableChecker;

class RequestTypeApplicableChecker extends MatchApplicableChecker
{
    /**
     * {@inheritdoc}
     */
    public function isApplicable(ContextInterface $context, array $processorAttributes)
    {
        $result   = self::ABSTAIN;
        $attrName = 'requestType';
        if (!empty($processorAttributes[$attrName])
            && $context->has($attrName)
            && !$this->isMatch($processorAttributes[$attrName], $context->get($attrName))
        ) {
            $result = self::NOT_APPLICABLE;
        }

        return $result;
    }
}
