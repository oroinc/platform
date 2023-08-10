<?php

namespace Oro\Bundle\EntityExtendBundle\Extend;

/**
 * Represents a result of {@see \Oro\Bundle\EntityExtendBundle\Extend\EntityExtendUpdateHandlerInterface::update}.
 */
final class EntityExtendUpdateResult
{
    public function __construct(
        private bool $successful,
        private ?string $failureMessage = null,
        private array $resultData = [],
    ) {
    }

    /**
     * Indicates whether the schema was successfully updated or not.
     */
    public function isSuccessful(): bool
    {
        return $this->successful;
    }

    /**
     * Gets the message to be shown to a user when the update schema failed.
     */
    public function getFailureMessage(): ?string
    {
        return $this->failureMessage;
    }

    public function getResultData(): array
    {
        return $this->resultData;
    }
}
