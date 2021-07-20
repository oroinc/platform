<?php

namespace Oro\Bundle\TranslationBundle\Provider;

use Oro\Bundle\TranslationBundle\Controller\Controller;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Filesystem\Exception\IOException;

/**
 * Dump js translations to appropriate files
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

    /**
     * @var Router
     */
    protected $router;
    /**
     * @var string
     */
    protected $jsTranslationRoute;

    /**
     * @param Controller       $translationController
     * @param Router           $router
     * @param array            $translationDomains
     * @param string           $kernelProjectDir
     * @param LanguageProvider $languageProvider
     * @param string           $jsTranslationRoute
     */
    public function __construct(
        Controller $translationController,
        Router $router,
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

            $this->logger->info(
                sprintf(
                    '<comment>%s</comment> <info>[file+]</info> %s',
                    date('H:i:s'),
                    basename($target)
                )
            );

            $content = $this->translationController->renderJsTranslationContent($this->translationDomains, $locale);

            $dirName = dirname($target);
            if (!is_dir($dirName) && true !== @mkdir($dirName, 0777, true)) {
                throw new IOException(sprintf('Failed to create %s', $dirName));
            }

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
        $targetPattern = realpath($this->kernelProjectDir . '/public')
            . $this->router->getRouteCollection()->get($this->jsTranslationRoute)->getPath();

        return strtr($targetPattern, ['{_locale}' => $locale]);
    }
}
