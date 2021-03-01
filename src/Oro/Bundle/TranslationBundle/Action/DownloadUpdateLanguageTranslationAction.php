<?php
declare(strict_types=1);

namespace Oro\Bundle\TranslationBundle\Action;

use Oro\Bundle\TranslationBundle\Download\TranslationDownloader;
use Oro\Component\ConfigExpression\ContextAccessor;

/**
 * Downloads and applies (loads to the database) translations for the specified language.
 *
 * The result (true if download succeeded and the translation were applied to the database, and false otherwise)
 * is set to the context attribute specified in the "result" parameter.
 *
 * Usage:
 *
 *  '@download_update_language_translation':
 *      language: "en_US"
 *      result: $.downloadedSuccessfully
 *
 *  '@download_update_language_translation':
 *      language: $.language
 *      result: $.downloadedSuccessfully
 */
class DownloadUpdateLanguageTranslationAction extends AbstractLanguageResultAction
{
    private TranslationDownloader $translationDownloader;

    public function __construct(ContextAccessor $actionContextAccessor, TranslationDownloader $translationDownloader)
    {
        parent::__construct($actionContextAccessor);
        $this->translationDownloader = $translationDownloader;
    }

    protected function executeAction($context): void
    {
        $result = null;
        try {
            $this->translationDownloader->downloadAndApplyTranslations($this->getLanguageCode($context));
            $result = true;
        } catch (\Throwable $e) {
            $result = false;
        }
        $this->contextAccessor->setValue($context, $this->resultPropertyPath, $result);
    }
}
