<?php

namespace Oro\Component\ChainProcessor;

class ProcessorApplicableCheckerFactory implements ProcessorApplicableCheckerFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function createApplicableChecker()
    {
        $applicableChecker = new ChainApplicableChecker();
        $applicableChecker->addChecker(new MatchApplicableChecker());
        $applicableChecker->addChecker(new SkipGroupApplicableChecker());
        $applicableChecker->addChecker(new GroupRangeApplicableChecker());
        
        return $applicableChecker;
    }
}
