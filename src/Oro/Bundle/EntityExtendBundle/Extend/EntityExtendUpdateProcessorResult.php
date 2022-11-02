<?php

namespace Oro\Bundle\EntityExtendBundle\Extend;

/**
 * Represents a result of {@see \Oro\Bundle\EntityExtendBundle\Extend\EntityExtendUpdateProcessor::processUpdate}.
 */
class EntityExtendUpdateProcessorResult
{
    private bool $successful;
    private ?string $internalFailureMessage;

    public function __construct(bool $successful, string $internalFailureMessage = null)
    {
        $this->successful = $successful;
        $this->internalFailureMessage = $internalFailureMessage;
    }

    /**
     * Indicates whether the schema was successfully updated or not.
     */
    public function isSuccessful(): bool
    {
        return $this->successful;
    }

    /**
     * Gets the message to be shown to a admin in console when the update schema failed.
     */
    public function getInternalFailureMessage(): ?string
    {
        return $this->internalFailureMessage;
    }
}
