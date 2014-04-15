<?php

namespace Oro\Bundle\UIBundle\Cache;

use Symfony\Bundle\TwigBundle\CacheWarmer\TemplateCacheCacheWarmer as SymfonyTemplateCacheCacheWarmer;

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
            } catch (\Twig_Error $e) {
                // problem during compilation, give up
            }
        }
    }
}
