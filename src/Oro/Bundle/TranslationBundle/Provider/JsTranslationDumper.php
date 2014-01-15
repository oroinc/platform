<?php

namespace Oro\Bundle\TranslationBundle\Provider;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Filesystem\Exception\IOException;

use Oro\Bundle\TranslationBundle\Controller\Controller;

class JsTranslationDumper implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var Controller */
    protected $translationController;

    /** @var array */
    protected $translationDomains;

    /** @var string */
    protected $kernelRootDir;

    /** @var string */
    protected $defaultLocale;

    /**
     * @var Router
     */
    protected $router;
    /**
     * @var string
     */
    protected $jsTranslationRoute;

    /**
     * @param Controller $translationController
     * @param Router     $router
     * @param array      $translationDomains
     * @param string     $kernelRootDir
     * @param string     $defaultLocale
     * @param string     $jsTranslationRoute
     */
    public function __construct(
        Controller $translationController,
        Router $router,
        $translationDomains,
        $kernelRootDir,
        $defaultLocale,
        $jsTranslationRoute = 'oro_translation_jstranslation'
    ) {
        $this->translationController = $translationController;
        $this->router                = $router;
        $this->translationDomains    = $translationDomains;
        $this->kernelRootDir         = $kernelRootDir;
        $this->defaultLocale         = $defaultLocale;
        $this->jsTranslationRoute    = $jsTranslationRoute;

        $this->setLogger(new NullLogger());
    }

    /**
     * @param array         $locales
     *
     * @return bool
     * @throws \Symfony\Component\Filesystem\Exception\IOException
     * @throws \RuntimeException
     */
    public function dumpTranslations($locales = [])
    {
        if (empty($locales)) {
            $locales[] = $this->defaultLocale;
        }

        $targetPattern = realpath($this->kernelRootDir . '/../web')
            . $this->router->getRouteCollection()->get($this->jsTranslationRoute)->getPath();

        foreach ($locales as $locale) {
            $target = strtr($targetPattern, array('{_locale}' => $locale));

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
                throw new \RuntimeException('Unable to write file ' . $target);
            }
        }

        return true;
    }
}
