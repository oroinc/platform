<?php

namespace Oro\Component\ChainProcessor;

/**
 * The base class for action processors.
 */
class ActionProcessor extends ChainProcessor
{
    /**
     * Creates an instance of ContextInterface this processor works with
     *
     * @return ContextInterface
     */
    public function createContext()
    {
        return new Context();
    }
}
