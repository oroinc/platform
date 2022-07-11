<?php

namespace Oro\Bundle\TranslationBundle\Translation;

/**
 * Translation messages sanitizer.
 * Removes XSS unsafe constructions from translation messages using HTMLPurifier.
 */
class TranslationsSanitizer
{
    private TranslationMessageSanitizerInterface $translationMessageSanitizer;

    public function __construct(TranslationMessageSanitizerInterface $translationMessageSanitizer)
    {
        $this->translationMessageSanitizer = $translationMessageSanitizer;
    }

    /**
     * Sanitizes the given translation messages.
     *
     * @param array  $translations [domain => [message id => message, ...], ...]
     * @param string $locale
     *
     * @return TranslationMessageSanitizationError[]
     */
    public function sanitizeTranslations(array $translations, string $locale): array
    {
        $sanitizationErrors = [];
        foreach ($translations as $domain => $messages) {
            foreach ($messages as $key => $message) {
                if (!$message) {
                    continue;
                }
                if (!$this->translationMessageSanitizer->isMessageSanitizationRequired($message)) {
                    continue;
                }

                $sanitizedMessage = $this->translationMessageSanitizer->sanitizeMessage($message);
                if ($sanitizedMessage === $message) {
                    continue;
                }

                $sanitizationErrors[] = new TranslationMessageSanitizationError(
                    $locale,
                    $domain,
                    $key,
                    $message,
                    $sanitizedMessage
                );
            }
        }

        return $sanitizationErrors;
    }
}
