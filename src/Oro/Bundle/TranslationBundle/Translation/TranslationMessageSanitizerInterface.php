<?php

namespace Oro\Bundle\TranslationBundle\Translation;

/**
 * Represents a sanitizer of translation messages.
 */
interface TranslationMessageSanitizerInterface
{
    public function isMessageSanitizationRequired(string $message): bool;

    public function sanitizeMessage(string $message): string;
}
