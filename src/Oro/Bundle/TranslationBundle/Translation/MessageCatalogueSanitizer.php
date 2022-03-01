<?php

namespace Oro\Bundle\TranslationBundle\Translation;

use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use Symfony\Component\Translation\MessageCatalogueInterface;

/**
 * Translation message catalogue sanitizer.
 * Removes XSS unsafe constructions from translation messages using HTMLPurifier.
 */
class MessageCatalogueSanitizer implements MessageCatalogueSanitizerInterface
{
    private HtmlTagHelper $htmlTagHelper;
    private array $sanitizationErrors = [];

    public function __construct(HtmlTagHelper $htmlTagHelper)
    {
        $this->htmlTagHelper = $htmlTagHelper;
    }

    public function sanitizeCatalogue(MessageCatalogueInterface $catalogue): void
    {
        foreach ($catalogue->all() as $domain => $messages) {
            $hasChanges = false;

            foreach ($messages as $key => $message) {
                if (!$message) {
                    continue;
                }
                // Skip message if it doesn't contain tags (nothing to sanitize)
                if (strip_tags($message) === $message) {
                    continue;
                }

                $sanitizedMessage = $this->getSanitizedTranslationMessage($message);

                // Do not replace message if nothing was removed by sanitizer.
                if ($sanitizedMessage === $message) {
                    continue;
                }

                $hasChanges = true;

                $this->sanitizationErrors[] = new SanitizationErrorInformation(
                    $catalogue->getLocale(),
                    $domain,
                    $key,
                    $message,
                    $sanitizedMessage
                );

                $messages[$key] = $sanitizedMessage ?: '';
            }

            if ($hasChanges) {
                $catalogue->replace($messages, $domain);
            }
        }
    }

    public function getSanitizationErrors(): array
    {
        return $this->sanitizationErrors;
    }

    private function getSanitizedTranslationMessage(string $message): ?string
    {
        $message = $this->applyFixesBeforeSanitization($message);
        // HtmlTagHelper should not collect errors (argument $collectErrors must be false) during sanitization
        // as it leads to undesired calls to Translator that makes it try to initialize in the middle of previous
        // initialization that leads to unpredictable behavior, e.g. losing fallback locales.
        $sanitizedMessage = $this->htmlTagHelper->sanitize($message, 'default', false);
        $sanitizedMessage = $this->applyFixesAfterSanitization($sanitizedMessage);

        return $sanitizedMessage;
    }

    private function applyFixesBeforeSanitization(string $message): string
    {
        // HTMLSanitizer has an issue and trim > symbol in a pair of <> symbols. Fixing it manually.
        return str_replace('<>', '&lt;&gt;', $message);
    }

    private function applyFixesAfterSanitization(?string $sanitizedMessage): ?string
    {
        if (null === $sanitizedMessage) {
            return null;
        }

        $undecodeAttributes = static function (array $matches) {
            return '="' . urldecode($matches[1]) . '"';
        };

        // Fix variable sanitization in attributes
        if (strpos($sanitizedMessage, '="%25') !== false) {
            $sanitizedMessage = preg_replace_callback(
                '/="(%25((%20)?)(\w+)((%20)?)%25)"/',
                $undecodeAttributes,
                $sanitizedMessage
            );
        }
        if (strpos($sanitizedMessage, '="%7B%7B') !== false) {
            $sanitizedMessage = preg_replace_callback(
                '/="(%7B%7B((%20)?)(\w+)((%20)?)%7D%7D)"/',
                $undecodeAttributes,
                $sanitizedMessage
            );
        }
        if (strpos($sanitizedMessage, '="%7B') !== false) {
            $sanitizedMessage = preg_replace_callback(
                '/="(%7B((%20)?)(\w+)((%20)?)%7D)"/',
                $undecodeAttributes,
                $sanitizedMessage
            );
        }

        return $sanitizedMessage;
    }
}
