<?php

namespace Oro\Bundle\LayoutBundle\Provider\Image;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Provides the image placeholder from the child provider which return it first.
 */
class ChainImagePlaceholderProvider implements ImagePlaceholderProviderInterface
{
    /** @var ImagePlaceholderProviderInterface[] */
    private array $providers = [];

    public function addProvider(ImagePlaceholderProviderInterface $provider): void
    {
        $this->providers[] = $provider;
    }

    public function getPath(
        string $filter,
        string $format = '',
        int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH
    ): ?string {
        $path = null;

        foreach ($this->providers as $provider) {
            $path = $provider->getPath($filter, $format, $referenceType);
            if ($path) {
                break;
            }
        }

        return $path;
    }
}
