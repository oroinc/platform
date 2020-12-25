<?php

namespace Oro\Bundle\TranslationBundle\Cache;

/**
 * Represents a result of
 * {@see \Oro\Bundle\TranslationBundle\Cache\RebuildTranslationCacheHandlerInterface::rebuildCache}.
 */
class RebuildTranslationCacheResult
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
     * Indicates whether the translation cache was successfully rebuilt or not.
     *
     * @return bool
     */
    public function isSuccessful(): bool
    {
        return $this->successful;
    }

    /**
     * Gets the message to be shown to a user when the translation cache rebuild failed.
     *
     * @return string|null
     */
    public function getFailureMessage(): ?string
    {
        return $this->failureMessage;
    }
}
