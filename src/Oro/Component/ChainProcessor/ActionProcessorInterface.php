<?php

namespace Oro\Component\ChainProcessor;

interface ActionProcessorInterface extends ProcessorInterface
{
    /**
     * Gets an action that is handled by the processor.
     *
     * @return string
     */
    public function getAction();

    /**
     * Creates an instance of Context this processor works with.
     *
     * @return Context
     */
    public function createContext();
}
