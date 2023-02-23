<?php

namespace Oro\Component\ChainProcessor;

/**
 * Represents a main processor for an action.
 */
interface ActionProcessorInterface extends ProcessorInterface
{
    /**
     * Gets an action that is handled by the processor.
     */
    public function getAction(): string;

    /**
     * Creates an instance of Context this processor works with.
     */
    public function createContext(): Context;
}
