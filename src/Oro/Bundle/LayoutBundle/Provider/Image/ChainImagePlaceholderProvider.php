<?php

namespace Oro\Bundle\LayoutBundle\Provider\Image;

/**
 * Provides the image placeholder from the child provider which return it first.
 */
class ChainImagePlaceholderProvider implements ImagePlaceholderProviderInterface
{
    /** @var array|ImagePlaceholderProviderInterface[] */
    private $providers = [];

    public function addProvider(ImagePlaceholderProviderInterface $provider): void
    {
        $this->providers[] = $provider;
    }

    /**
     * {@inheritdoc}
     */
    public function getPath(string $filter): ?string
    {
        $path = null;

        foreach ($this->providers as $provider) {
            $path = $provider->getPath($filter);
            if ($path) {
                break;
            }
        }

        return $path;
    }
}
