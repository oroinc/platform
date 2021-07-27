<?php

namespace Oro\Bundle\LayoutBundle\Cache;

use Psr\Container\ContainerInterface;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Environment;
use Twig\Error\Error;

/**
 * Generates the Twig cache for all templates.
 * This cache warmer is a copy of {@see \Symfony\Bundle\TwigBundle\CacheWarmer\TemplateCacheWarmer},
 * but it skips layout update files in "layouts" directories.
 */
class TwigTemplateCacheWarmer implements CacheWarmerInterface, ServiceSubscriberInterface
{
    private ContainerInterface $container;
    private iterable $iterator;
    private ?Environment $twig = null;

    public function __construct(ContainerInterface $container, iterable $iterator)
    {
        // As this cache warmer is optional, dependencies should be lazy-loaded,
        // that's why a container should be injected.
        $this->container = $container;
        $this->iterator = $iterator;
    }

    /**
     * {@inheritdoc}
     */
    public function warmUp($cacheDir)
    {
        if (null === $this->twig) {
            $this->twig = $this->container->get('twig');
        }

        foreach ($this->iterator as $template) {
            if ($this->isLayoutUpdateFile($template)) {
                continue;
            }
            try {
                $this->twig->load($template);
            } catch (Error $e) {
                // problem during compilation, give up
                // might be a syntax error or a non-Twig template
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isOptional()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return [
            'twig' => Environment::class
        ];
    }

    private function isLayoutUpdateFile(string $file): bool
    {
        return
            str_ends_with($file, '.yml')
            && preg_match('/^@\w+\/layouts\//', $file);
    }
}
