<?php

namespace Oro\Component\ChainProcessor;

/**
 * Represents a factory to create an applicable checker that should be used to check
 * whether a processor should be executed or not.
 */
interface ProcessorApplicableCheckerFactoryInterface
{
    /**
     * Creates an applicable checker that can be used to check whether a processor should be executed or not.
     */
    public function createApplicableChecker(): ChainApplicableChecker;
}
