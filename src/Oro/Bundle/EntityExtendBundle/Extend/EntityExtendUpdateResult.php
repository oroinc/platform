<?php

namespace Oro\Bundle\EntityExtendBundle\Extend;

/**
 * Represents a result of {@see \Oro\Bundle\EntityExtendBundle\Extend\EntityExtendUpdateHandlerInterface::update}.
 */
final class EntityExtendUpdateResult
{
    /** @var bool */
    private $successful;

    /** @var string|null */
    private $failureMessage;

    /**
     * @param bool        $successful
     * @param string|null $failureMessage
     */
    public function __construct(bool $successful, string $failureMessage = null)
    {
        $this->successful = $successful;
        $this->failureMessage = $failureMessage;
    }


    /**
     * Indicates whether the schema was successfully updated or not.
     *
     * @return bool
     */
    public function isSuccessful(): bool
    {
        return $this->successful;
    }

    /**
     * Gets the message to be shown to a user when the update schema failed.
     *
     * @return string|null
     */
    public function getFailureMessage(): ?string
    {
        return $this->failureMessage;
    }
}
