<?php

namespace Oro\Bundle\ApiBundle\Processor;

use Oro\Component\ChainProcessor\ChainApplicableChecker;
use Oro\Component\ChainProcessor\ProcessorApplicableCheckerFactoryInterface;

/**
 * Creates an applicable checker that should be used to check whether API processor should be executed or not.
 */
class ProcessorApplicableCheckerFactory implements ProcessorApplicableCheckerFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function createApplicableChecker()
    {
        $applicableChecker = new ChainApplicableChecker();
        $applicableChecker->addChecker(
            new MatchApplicableChecker(['group'], ['class', 'parentClass'])
        );

        return $applicableChecker;
    }
}
