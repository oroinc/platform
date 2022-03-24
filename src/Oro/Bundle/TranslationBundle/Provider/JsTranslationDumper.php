<?php

namespace Oro\Bundle\TranslationBundle\Provider;

use Oro\Bundle\GaufretteBundle\FileManager;
use Oro\Bundle\TranslationBundle\Controller\Controller;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Routing\RouterInterface;

/**
 * Dump JS translations to files.
 */
class JsTranslationDumper implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var Controller */
    protected $translationController;

    /** @var array */
    protected $translationDomains;

    /** @var string */
    protected $kernelProjectDir;

    /** @var LanguageProvider */
    protected $languageProvider;

    /** @var RouterInterface */
    protected $router;

    /** @var string */
    protected $jsTranslationRoute;

    private FileManager $fileManager;

    /**
     * @param Controller       $translationController
     * @param RouterInterface  $router
     * @param array            $translationDomains
     * @param string           $kernelProjectDir
     * @param LanguageProvider $languageProvider
     * @param string           $jsTranslationRoute
     */
    public function __construct(
        Controller $translationController,
        RouterInterface $router,
        $translationDomains,
        $kernelProjectDir,
        LanguageProvider $languageProvider,
        $jsTranslationRoute = 'oro_translation_jstranslation'
    ) {
        $this->translationController = $translationController;
        $this->router                = $router;
        $this->translationDomains    = $translationDomains;
        $this->kernelProjectDir      = $kernelProjectDir;
        $this->languageProvider      = $languageProvider;
        $this->jsTranslationRoute    = $jsTranslationRoute;

        $this->setLogger(new NullLogger());
    }

    public function setFileManager(FileManager $fileManager): void
    {
        $this->fileManager = $fileManager;
    }

    /**
     * @param array         $locales
     *
     * @return bool
     * @throws IOException
     */
    public function dumpTranslations($locales = [])
    {
        if (empty($locales)) {
            $locales = $this->languageProvider->getAvailableLanguageCodes();
        }

        foreach ($locales as $locale) {
            $target = $this->getTranslationFilePath($locale);
            $this->logger->info('<info>[file+]</info> ' . $target);

            $content = $this->translationController->renderJsTranslationContent($this->translationDomains, $locale);
            if (false === @file_put_contents($target, $content)) {
                throw new IOException('Unable to write file ' . $target);
            }
        }

        return true;
    }

    public function isTranslationFileExist(string $locale): bool
    {
        $translationFilePath = $this->getTranslationFilePath($locale);

        return file_exists($translationFilePath);
    }

    private function getTranslationFilePath(string $locale): string
    {
        return $this->fileManager->getFilePath(sprintf('translation/%s.json', $locale));
    }
}
