<?php

namespace Oro\Bundle\TranslationBundle\Cache;

use Oro\Bundle\TranslationBundle\Provider\JsTranslationDumper;
use Oro\Bundle\TranslationBundle\Provider\TranslationDomainProvider;
use Oro\Bundle\TranslationBundle\Translation\Translator;
use Oro\Bundle\UIBundle\Asset\DynamicAssetVersionManager;
use Psr\Log\LoggerInterface;

/**
 * Provides a way to rebuild the translation cache.
 */
class RebuildTranslationCacheProcessor
{
    /** @var Translator */
    private $translator;

    /** @var TranslationDomainProvider */
    private $domainProvider;

    /** @var JsTranslationDumper */
    private $jsTranslationDumper;

    /** @var DynamicAssetVersionManager */
    private $assetVersionManager;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        Translator $translator,
        TranslationDomainProvider $domainProvider,
        JsTranslationDumper $jsTranslationDumper,
        DynamicAssetVersionManager $assetVersionManager,
        LoggerInterface $logger
    ) {
        $this->translator = $translator;
        $this->domainProvider = $domainProvider;
        $this->jsTranslationDumper = $jsTranslationDumper;
        $this->assetVersionManager = $assetVersionManager;
        $this->logger = $logger;
    }

    /**
     * Rebuilds the translation cache.
     */
    public function rebuildCache(): bool
    {
        try {
            $this->clearTranslationDomainCache();
            $this->rebuildTranslationCache();
            $this->dumpJsTranslations();
            $this->updateTranslationAssetVersion();
        } catch (\RuntimeException $e) {
            $this->logger->error(
                'Failed to rebuild the translation cache.',
                ['exception' => $e]
            );

            return false;
        }

        return true;
    }

    private function clearTranslationDomainCache(): void
    {
        try {
            $this->domainProvider->clearCache();
        } catch (\Throwable $e) {
            throw new \RuntimeException(
                'The clearing of available translation domains cache failed.',
                $e->getCode(),
                $e
            );
        }
    }

    private function rebuildTranslationCache(): void
    {
        try {
            $this->translator->rebuildCache();
        } catch (\Throwable $e) {
            throw new \RuntimeException(
                'The rebuilding of cached translation message catalogs failed.',
                $e->getCode(),
                $e
            );
        }
    }

    private function dumpJsTranslations(): void
    {
        try {
            $this->jsTranslationDumper->dumpTranslations();
        } catch (\Throwable $e) {
            throw new \RuntimeException(
                'The dumping of JS translations failed.',
                $e->getCode(),
                $e
            );
        }
    }

    private function updateTranslationAssetVersion(): void
    {
        try {
            $this->assetVersionManager->updateAssetVersion('translations');
        } catch (\Throwable $e) {
            throw new \RuntimeException(
                'The updating of the translations asset version failed.',
                $e->getCode(),
                $e
            );
        }
    }
}
