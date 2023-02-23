<?php

namespace Oro\Component\ChainProcessor;

/**
 * The factory to create an applicable checker that should be used to check
 * whether a processor should be executed or not.
 */
class ProcessorApplicableCheckerFactory implements ProcessorApplicableCheckerFactoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function createApplicableChecker(): ChainApplicableChecker
    {
        $applicableChecker = new ChainApplicableChecker();
        $applicableChecker->addChecker(new MatchApplicableChecker());
        $applicableChecker->addChecker(new SkipGroupApplicableChecker());
        $applicableChecker->addChecker(new GroupRangeApplicableChecker());

        return $applicableChecker;
    }
}
