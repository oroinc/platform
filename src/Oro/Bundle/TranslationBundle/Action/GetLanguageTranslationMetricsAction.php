<?php
declare(strict_types=1);

namespace Oro\Bundle\TranslationBundle\Action;

use Oro\Bundle\TranslationBundle\Download\TranslationMetricsProviderInterface;
use Oro\Component\ConfigExpression\ContextAccessor;

/**
 * Provides translation metrics for the specified language.
 *
 * The result (array if available or null if none) is set to the context attribute specified in the "result" parameter:
 * [
 *     'code' => 'uk_UA',                       // full language code, including locality
 *     'altCode' => 'uk',                       // optional, may not be present
 *     'translationStatus' => 30,               // percentage of translated strings or words (varies by service)
 *     'lastBuildDate' => \DateTimeInterface    // object with the last translation build date
 * ]
 *
 * Usage:
 *
 *  '@get_language_translation_metrics':
 *      language: "uk_UA"
 *      result: $.translationMetrics
 *
 *  '@get_language_translation_metrics':
 *      language: $.language
 *      result: $.translationMetrics
 */
class GetLanguageTranslationMetricsAction extends AbstractLanguageResultAction
{
    private TranslationMetricsProviderInterface $translationMetricsProvider;

    public function __construct(
        ContextAccessor $actionContextAccessor,
        TranslationMetricsProviderInterface $translationMetricsProvider
    ) {
        parent::__construct($actionContextAccessor);
        $this->translationMetricsProvider = $translationMetricsProvider;
    }

    protected function executeAction($context): void
    {
        $metrics = null;
        try {
            $metrics = $this->translationMetricsProvider->getForLanguage($this->getLanguageCode($context));
        } catch (\Throwable $e) {
            $metrics = null;
        }
        $this->contextAccessor->setValue($context, $this->resultPropertyPath, $metrics);
    }
}
