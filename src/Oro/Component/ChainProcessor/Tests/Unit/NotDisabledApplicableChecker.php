<?php

namespace Oro\Component\ChainProcessor\Tests\Unit;

use Oro\Component\ChainProcessor\ApplicableCheckerInterface;
use Oro\Component\ChainProcessor\ContextInterface;

class NotDisabledApplicableChecker implements ApplicableCheckerInterface
{
    /**
     * {@inheritdoc}
     */
    public function isApplicable(ContextInterface $context, array $processorAttributes)
    {
        $result = self::ABSTAIN;
        if (isset($processorAttributes['disabled']) && $processorAttributes['disabled']) {
            $result = self::NOT_APPLICABLE;
        }

        return $result;
    }
}
