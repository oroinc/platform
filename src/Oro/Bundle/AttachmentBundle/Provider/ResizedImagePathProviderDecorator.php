<?php

namespace Oro\Bundle\AttachmentBundle\Provider;

use Oro\Bundle\AttachmentBundle\Entity\File;

/**
 * Resized image path provider decorator that cuts configured prefix.
 */
class ResizedImagePathProviderDecorator implements ResizedImagePathProviderInterface
{
    /** @var ResizedImagePathProviderInterface */
    private $resizedImagePathProvider;

    /** @var string */
    private $skipPrefix;

    /**
     * @param ResizedImagePathProviderInterface $resizedImagePathProvider
     * @param string $skipPrefix
     */
    public function __construct(ResizedImagePathProviderInterface $resizedImagePathProvider, string $skipPrefix)
    {
        $this->resizedImagePathProvider = $resizedImagePathProvider;
        $this->skipPrefix = '/' . trim($skipPrefix, '/');
    }

    /**
     * {@inheritdoc}
     */
    public function getPathForResizedImage(
        File $entity,
        int $width,
        int $height
    ): string {
        $path = $this->resizedImagePathProvider->getPathForResizedImage($entity, $width, $height);

        return $this->preparePath($path);
    }

    /**
     * {@inheritdoc}
     */
    public function getPathForFilteredImage(
        File $entity,
        string $filterName
    ): string {
        $path = $this->resizedImagePathProvider->getPathForFilteredImage($entity, $filterName);

        return $this->preparePath($path);
    }

    /**
     * Cuts off the prefix (e.g. admin/media/cache) from the beginning of the image path.
     *
     * @param string $path
     *
     * @return string
     */
    private function preparePath(string $path): string
    {
        $path = '/' . ltrim($path, '/');

        if (stripos($path, $this->skipPrefix) === 0) {
            $path = substr_replace($path, '', 0, \strlen($this->skipPrefix));
        }

        return $path;
    }
}
