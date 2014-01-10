<?php

namespace Oro\Bundle\NavigationBundle\Title;

use Symfony\Component\Routing\Router;
use Symfony\Component\Translation\MessageCatalogue;
use Symfony\Component\Translation\Extractor\ExtractorInterface;

use Oro\Bundle\NavigationBundle\Provider\TitleServiceInterface;

class TranslationExtractor implements ExtractorInterface
{
    /** @var TitleServiceInterface */
    private $titleService;

    /** @var Router */
    private $router;

    /** @var string */
    private $prefix;

    /**
     * @param TitleServiceInterface $titleService
     * @param Router                $router
     */
    public function __construct(TitleServiceInterface $titleService, Router $router)
    {
        $this->titleService = $titleService;
        $this->router       = $router;
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
        $routes = $this->getRoutesByBundleDir($directory);

        $titles = $this->titleService->getStoredTitlesRepository()->getTitles($routes);

        foreach ($titles as $titleRecord) {
            $catalogue->set($titleRecord['shortTitle'], $this->prefix . $titleRecord['shortTitle']);
            $catalogue->set($titleRecord['title'], $this->prefix . $titleRecord['title']);
        }

        return $catalogue;
    }

    /**
     * Get routes by bundle dir
     *
     * @param string $dir
     *
     * @return array|Router
     */
    public function getRoutesByBundleDir($dir)
    {
        $routes = $this->router->getRouteCollection()->all();

        $resultRoutes = array();
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
    public function getBundleNameFromString($string)
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
