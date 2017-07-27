<?php

namespace Oro\Bundle\UIBundle\Twig;

class Environment extends \Twig_Environment
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
            $this->writeCacheFile($cache, $this->compileSource($this->getLoader()->getSource($name), $name));
        }
    }
}
