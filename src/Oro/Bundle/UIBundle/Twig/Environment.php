<?php

namespace Oro\Bundle\UIBundle\Twig;

use Twig\Environment as TwigEnvironment;

/**
 * Extends base Twig Environment class
 */
class Environment extends TwigEnvironment
{
    /**
     * Generates a template cache file by template name.
     *
     * @param string  $name  The template name
     */
    public function generateTemplateCache($name)
    {
        if (false !== $cache = $this->getCacheFilename($name)) {
            $this->generateCache($cache, $name);
        }
    }

    /**
     * Generates a template cache file.
     *
     * @param string|false $cache The cache file name or false when caching is disabled
     * @param string       $name  The template name
     */
    protected function generateCache($cache, $name)
    {
        if (!is_file($cache) || ($this->isAutoReload() && !$this->isTemplateFresh($name, filemtime($cache)))) {
            $this->writeCacheFile($cache, $this->compileSource($this->getLoader()->getSourceContext($name)));
        }
    }

    /**
     * Gets the cache filename for a given template.
     *
     * @param string $name The template name
     *
     * @return string|false The cache file name or false when caching is disabled
     */
    private function getCacheFilename($name)
    {
        $key = $this->getCache(false)->generateKey($name, $this->getTemplateClass($name));

        return !$key ? false : $key;
    }

    /**
     * Writes the compiled template to cache.
     *
     * @param string $key The cache key
     * @param string $content The template representation as a PHP class
     */
    private function writeCacheFile($key, $content)
    {
        $this->getCache(false)->write($key, $content);
    }
}
