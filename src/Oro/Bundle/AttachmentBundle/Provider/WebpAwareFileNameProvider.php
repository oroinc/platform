<?php

namespace Oro\Bundle\AttachmentBundle\Provider;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Tools\WebpConfiguration;

/**
 * Sets default format to webp if webp strategy is enabled for all.
 */
class WebpAwareFileNameProvider implements FileNameProviderInterface
{
    private FileNameProviderInterface $innerFileNameProvider;

    private WebpConfiguration $webpConfiguration;

    public function __construct(
        FileNameProviderInterface $innerFileNameProvider,
        WebpConfiguration $webpConfiguration
    ) {
        $this->innerFileNameProvider = $innerFileNameProvider;
        $this->webpConfiguration = $webpConfiguration;
    }

    /**
     * {@inheritdoc}
     */
    public function getFileName(File $file, string $format = ''): string
    {
        if (!$format && $this->webpConfiguration->isEnabledForAll()) {
            $format = 'webp';
        }

        return $this->innerFileNameProvider->getFileName($file, $format);
    }
}
