<?php

namespace Oro\Bundle\EmailBundle\Twig\EmailTemplateLoader;

use Oro\Bundle\EmailBundle\Model\EmailTemplate as EmailTemplateModel;
use Twig\Error\LoaderError;
use Twig\Loader\ChainLoader;
use Twig\Loader\LoaderInterface;
use Twig\Source;

/**
 * Chained email template loader that just calls its inner loaders.
 */
class EmailTemplateChainLoader implements EmailTemplateLoaderInterface
{
    private ChainLoader $chainLoader;

    /**
     * @param ChainLoader $chainLoader
     * @param iterable<EmailTemplateLoaderInterface> $loaders
     */
    public function __construct(ChainLoader $chainLoader, iterable $loaders = [])
    {
        $this->chainLoader = $chainLoader;

        foreach ($loaders as $loader) {
            $this->chainLoader->addLoader($loader);
        }
    }

    public function addLoader(LoaderInterface $loader): void
    {
        $this->chainLoader->addLoader($loader);
    }

    /**
     * @return EmailTemplateLoaderInterface[]
     */
    public function getLoaders(): array
    {
        return $this->chainLoader->getLoaders();
    }

    public function getSourceContext(string $name): Source
    {
        return $this->chainLoader->getSourceContext($name);
    }

    public function getCacheKey(string $name): string
    {
        return $this->chainLoader->getCacheKey($name);
    }

    public function isFresh(string $name, int $time): bool
    {
        return $this->chainLoader->isFresh($name, $time);
    }

    public function exists(string $name): bool
    {
        return $this->chainLoader->exists($name);
    }

    public function getEmailTemplate(string $name): EmailTemplateModel
    {
        $exceptions = [];
        foreach ($this->getLoaders() as $loader) {
            if (!$loader->exists($name)) {
                continue;
            }

            try {
                return $loader->getEmailTemplate($name);
            } catch (LoaderError $e) {
                $exceptions[] = $e->getMessage();
            }
        }

        throw new LoaderError(
            sprintf(
                'Template "%s" is not defined%s.',
                $name,
                $exceptions ? ' (' . implode(', ', $exceptions) . ')' : ''
            )
        );
    }
}
