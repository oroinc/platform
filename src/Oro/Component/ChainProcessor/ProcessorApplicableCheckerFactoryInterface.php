<?php

namespace Oro\Component\ChainProcessor;

interface ProcessorApplicableCheckerFactoryInterface
{
    /**
     * Creates an applicable checker that can be used to check whether a processor should be executed or not.
     *
     * @return ChainApplicableChecker
     */
    public function createApplicableChecker();
}
