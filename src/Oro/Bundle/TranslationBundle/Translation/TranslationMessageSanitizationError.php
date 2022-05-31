<?php

namespace Oro\Bundle\TranslationBundle\Translation;

/**
 * Represents a translation message sanitization error.
 */
class TranslationMessageSanitizationError
{
    private string $locale;
    private string $domain;
    private string $messageKey;
    private string $originalMessage;
    private string $sanitizedMessage;

    public function __construct(
        string $locale,
        string $domain,
        string $messageKey,
        string $originalMessage,
        string $sanitizedMessage
    ) {
        $this->locale = $locale;
        $this->domain = $domain;
        $this->messageKey = $messageKey;
        $this->originalMessage = $originalMessage;
        $this->sanitizedMessage = $sanitizedMessage;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function getDomain(): string
    {
        return $this->domain;
    }

    public function getMessageKey(): string
    {
        return $this->messageKey;
    }

    public function getOriginalMessage(): string
    {
        return $this->originalMessage;
    }

    public function getSanitizedMessage(): string
    {
        return $this->sanitizedMessage;
    }

    public function __toString()
    {
        return (string)json_encode([
            'locale' => $this->locale,
            'domain' => $this->domain,
            'messageKey' => $this->messageKey,
            'originalMessage' => $this->originalMessage,
            'sanitizedMessage' => $this->sanitizedMessage
        ]);
    }
}
