<?php

namespace Oro\Bundle\TranslationBundle\Translation;

use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;

/**
 * The sanitizer of translation messages.
 * Removes XSS unsafe constructions from translation messages using HTMLPurifier.
 */
class TranslationMessageSanitizer implements TranslationMessageSanitizerInterface
{
    private HtmlTagHelper $htmlTagHelper;

    public function __construct(HtmlTagHelper $htmlTagHelper)
    {
        $this->htmlTagHelper = $htmlTagHelper;
    }

    /**
     * {@inheritDoc}
     */
    public function isMessageSanitizationRequired(string $message): bool
    {
        return strip_tags($message) !== $message;
    }

    /**
     * {@inheritDoc}
     */
    public function sanitizeMessage(string $message): string
    {
        // HtmlTagHelper should not collect errors (argument $collectErrors must be false) during sanitization
        // as it leads to undesired calls to Translator that makes it try to initialize in the middle of previous
        // initialization that leads to unpredictable behavior, e.g. losing fallback locales.
        return $this->applyFixesAfterSanitization(
            $this->htmlTagHelper->sanitize($this->applyFixesBeforeSanitization($message), 'default', false)
        );
    }

    private function applyFixesBeforeSanitization(string $message): string
    {
        // HTMLSanitizer has an issue and trim > symbol in a pair of <> symbols. Fixing it manually.
        return str_replace('<>', '&lt;&gt;', $message);
    }

    private function applyFixesAfterSanitization(string $sanitizedMessage): string
    {
        if (!$sanitizedMessage) {
            return '';
        }

        $undecodeAttributes = static function (array $matches) {
            return '="' . urldecode($matches[1]) . '"';
        };

        // Fix variable sanitization in attributes
        if (str_contains($sanitizedMessage, '="%25')) {
            $sanitizedMessage = preg_replace_callback(
                '/="(%25((%20)?)(\w+)((%20)?)%25)"/',
                $undecodeAttributes,
                $sanitizedMessage
            );
        }
        if (str_contains($sanitizedMessage, '="%7B%7B')) {
            $sanitizedMessage = preg_replace_callback(
                '/="(%7B%7B((%20)?)(\w+)((%20)?)%7D%7D)"/',
                $undecodeAttributes,
                $sanitizedMessage
            );
        }
        if (str_contains($sanitizedMessage, '="%7B')) {
            $sanitizedMessage = preg_replace_callback(
                '/="(%7B((%20)?)(\w+)((%20)?)%7D)"/',
                $undecodeAttributes,
                $sanitizedMessage
            );
        }

        return $sanitizedMessage;
    }
}
