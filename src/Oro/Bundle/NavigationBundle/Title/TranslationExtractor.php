<?php

namespace Oro\Bundle\NavigationBundle\Title;

use Oro\Bundle\NavigationBundle\Title\TitleReader\TitleReaderRegistry;
use Oro\Bundle\UIBundle\Provider\ControllerClassProvider;
use Symfony\Component\Translation\Extractor\ExtractorInterface;
use Symfony\Component\Translation\MessageCatalogue;

/**
 * Extracts translation messages from page titles.
 */
class TranslationExtractor implements ExtractorInterface
{
    /** @var TitleReaderRegistry */
    private $titleReaderRegistry;

    /** @var ControllerClassProvider */
    private $controllerClassProvider;

    /** @var string */
    private $prefix;

    public function __construct(
        TitleReaderRegistry $titleReaderRegistry,
        ControllerClassProvider $controllerClassProvider
    ) {
        $this->titleReaderRegistry = $titleReaderRegistry;
        $this->controllerClassProvider = $controllerClassProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function extract($directory, MessageCatalogue $catalogue)
    {
        $routes = $this->getRoutesByBundleDir($directory);
        foreach ($routes as $route) {
            $shortTitle = $this->titleReaderRegistry->getTitleByRoute($route);
            $catalogue->set($shortTitle, $this->prefix . $shortTitle);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
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
        $resultRoutes = [];
        $controllers = $this->controllerClassProvider->getControllers();
        foreach ($controllers as $routeName => list($class, $method)) {
            if ($this->getBundleNameFromString($dir) === $this->getBundleNameFromString($class)) {
                $resultRoutes[] = $routeName;
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
}
