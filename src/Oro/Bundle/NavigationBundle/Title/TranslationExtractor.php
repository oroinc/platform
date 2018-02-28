<?php

namespace Oro\Bundle\NavigationBundle\Title;

use Oro\Bundle\NavigationBundle\Title\TitleReader\TitleReaderRegistry;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\Extractor\ExtractorInterface;
use Symfony\Component\Translation\MessageCatalogue;

class TranslationExtractor implements ExtractorInterface
{
    /** @var TitleReaderRegistry */
    private $titleReaderRegistry;

    /** @var RouterInterface */
    private $router;

    /** @var string */
    private $prefix;

    /**
     * @param TitleReaderRegistry   $titleReaderRegistry
     * @param RouterInterface                $router
     */
    public function __construct(
        TitleReaderRegistry $titleReaderRegistry,
        RouterInterface $router
    ) {
        $this->titleReaderRegistry = $titleReaderRegistry;
        $this->router = $router;
    }

    /**
     * Extract titles for translation
     *
     * @param string           $directory
     * @param MessageCatalogue $catalogue
     *
     * @return MessageCatalogue
     */
    public function extract($directory, MessageCatalogue $catalogue)
    {
        $titles = [];

        $routes = $this->getRoutesByBundleDir($directory);
        foreach ($routes as $route) {
            $titles[] = $this->titleReaderRegistry->getTitleByRoute($route);
        }

        foreach ($titles as $shortTitle) {
            $catalogue->set($shortTitle, $this->prefix . $shortTitle);
        }

        return $catalogue;
    }

    /**
     * Get routes by bundle dir
     *
     * @param string $dir
     *
     * @return array
     */
    private function getRoutesByBundleDir($dir)
    {
        $routes = $this->router->getRouteCollection()->all();

        $resultRoutes = [];
        /** @var \Symfony\Component\Routing\Route $route */
        foreach ($routes as $name => $route) {
            if ($this->getBundleNameFromString($dir) ==
                $this->getBundleNameFromString($route->getDefault('_controller'))
            ) {
                $resultRoutes[] = $name;
            }
        }

        return $resultRoutes;
    }

    /**
     * @param string $string
     *
     * @return bool|string
     */
    private function getBundleNameFromString($string)
    {
        $bundleName = false;
        if (preg_match('#[/|\\\]([\w]+Bundle)[/|\\\]#', $string, $match)) {
            $bundleName = $match[1];
        }

        return $bundleName;
    }

    /**
     * Set prefix for translated strings
     *
     * @param string $prefix
     */
    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
    }
}
