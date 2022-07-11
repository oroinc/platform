<?php

namespace Oro\Bundle\TranslationBundle\Translation;

/**
 * The collection of translation message sanitization errors.
 */
class TranslationMessageSanitizationErrorCollection
{
    /** @var TranslationMessageSanitizationError[] */
    private $errors = [];

    public function add(TranslationMessageSanitizationError $error): void
    {
        $errorId = sprintf('%s|%s|%s', $error->getLocale(), $error->getDomain(), $error->getMessageKey());
        if (!isset($this->errors[$errorId])) {
            $this->errors[$errorId] = $error;
        }
    }

    /**
     * @return TranslationMessageSanitizationError[]
     */
    public function all(): array
    {
        return array_values($this->errors);
    }

    public function clear(): void
    {
        $this->errors = [];
    }
}
