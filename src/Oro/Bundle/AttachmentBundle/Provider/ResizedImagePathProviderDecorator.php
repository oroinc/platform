<?php

namespace Oro\Bundle\AttachmentBundle\Provider;

use Oro\Bundle\AttachmentBundle\Entity\File;

/**
 * A decorator for a resized image path provider that removes a specified prefix from a path.
 */
class ResizedImagePathProviderDecorator implements ResizedImagePathProviderInterface
{
    /** @var ResizedImagePathProviderInterface */
    private $resizedImagePathProvider;

    /** @var string */
    private $prefix;

    /** @var int */
    private $prefixLength;

    public function __construct(ResizedImagePathProviderInterface $resizedImagePathProvider, string $prefix)
    {
        $this->resizedImagePathProvider = $resizedImagePathProvider;
        $this->prefix = '/' . trim($prefix, '/') . '/';
        $this->prefixLength = \strlen($this->prefix);
    }

    /**
     * {@inheritdoc}
     */
    public function getPathForResizedImage(File $entity, int $width, int $height, string $format = ''): string
    {
        return $this->removePrefix(
            $this->resizedImagePathProvider->getPathForResizedImage($entity, $width, $height, $format)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getPathForFilteredImage(File $entity, string $filterName, string $format = ''): string
    {
        return $this->removePrefix(
            $this->resizedImagePathProvider->getPathForFilteredImage($entity, $filterName, $format)
        );
    }

    private function removePrefix(string $path): string
    {
        $path = '/' . ltrim($path, '/');
        if (strncmp($path, $this->prefix, $this->prefixLength) === 0) {
            $path = substr($path, $this->prefixLength - 1);
        }

        return $path;
    }
}
