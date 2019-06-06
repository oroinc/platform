<?php

namespace Oro\Bundle\UIBundle\Cache;

use Symfony\Bundle\TwigBundle\CacheWarmer\TemplateCacheCacheWarmer as SymfonyTemplateCacheCacheWarmer;
use Twig\Error\Error;

/**
 * Overrides Symfony\Bundle\TwigBundle\CacheWarmer\TemplateCacheCacheWarmer->warmUp()
 * to improve template cache warm-up performance.
 */
class TemplateCacheCacheWarmer extends SymfonyTemplateCacheCacheWarmer
{
    /**
     * Warms up the cache.
     *
     * @param string $cacheDir The cache directory
     */
    public function warmUp($cacheDir)
    {
        /** @var \Oro\Bundle\UIBundle\Twig\Environment $twig */
        $twig = $this->container->get('twig');

        foreach ($this->finder->findAllTemplates() as $template) {
            if ('twig' !== $template->get('engine')) {
                continue;
            }

            try {
                $twig->generateTemplateCache($template);
            } catch (Error $e) {
                // problem during compilation, give up
            }
        }
    }
}
