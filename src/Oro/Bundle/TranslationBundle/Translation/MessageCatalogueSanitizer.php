<?php

namespace Oro\Bundle\TranslationBundle\Translation;

use Symfony\Component\Translation\MessageCatalogueInterface;

/**
 * Translation message catalogue sanitizer.
 * Removes XSS unsafe constructions from translation messages using HTMLPurifier.
 */
class MessageCatalogueSanitizer
{
    private TranslationsSanitizer $sanitizer;
    private TranslationMessageSanitizationErrorCollection $sanitizationError;

    public function __construct(
        TranslationsSanitizer $sanitizer,
        TranslationMessageSanitizationErrorCollection $sanitizationError
    ) {
        $this->sanitizer = $sanitizer;
        $this->sanitizationError = $sanitizationError;
    }

    /**
     * Sanitizes the given translation message catalogue.
     */
    public function sanitizeCatalogue(MessageCatalogueInterface $catalogue): void
    {
        $translations = $catalogue->all();
        $errors = $this->sanitizer->sanitizeTranslations($translations, $catalogue->getLocale());
        if ($errors) {
            $domains = [];
            foreach ($errors as $e) {
                $this->sanitizationError->add($e);
                $domain = $e->getDomain();
                $translations[$domain][$e->getMessageKey()] = $e->getSanitizedMessage();
                if (!isset($domains[$domain])) {
                    $domains[$domain] = $domain;
                }
            }
            foreach ($domains as $domain) {
                $catalogue->replace($translations[$domain], $domain);
            }
        }
    }
}
