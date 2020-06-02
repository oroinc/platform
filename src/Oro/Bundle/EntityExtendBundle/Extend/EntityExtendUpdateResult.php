<?php

namespace Oro\Bundle\EntityExtendBundle\Extend;

/**
 * Represents a result of {@see \Oro\Bundle\EntityExtendBundle\Extend\EntityExtendUpdateHandler::updateSchema}.
 */
final class EntityExtendUpdateResult
{
    /** @var bool */
    private $successful;

    /** @var string|null */
    private $failedMessage;

    /**
     * @param bool        $successful
     * @param string|null $failedMessage
     */
    public function __construct(bool $successful, string $failedMessage = null)
    {
        $this->successful = $successful;
        $this->failedMessage = $failedMessage;
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
    public function getFailedMessage(): ?string
    {
        return $this->failedMessage;
    }
}
