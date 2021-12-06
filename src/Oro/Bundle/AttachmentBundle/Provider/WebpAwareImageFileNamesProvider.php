<?php

namespace Oro\Bundle\AttachmentBundle\Provider;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Tools\WebpConfiguration;

/**
 * Provides filenames of all resized/filtered images for a specific File entity taking into account webp strategy.
 */
class WebpAwareImageFileNamesProvider implements FileNamesProviderInterface
{
    private FileNamesProviderInterface $innerFileNamesProvider;

    private WebpConfiguration $webpConfiguration;

    public function __construct(
        FileNamesProviderInterface $innerFileNamesProvider,
        WebpConfiguration $webpConfiguration
    ) {
        $this->innerFileNamesProvider = $innerFileNamesProvider;
        $this->webpConfiguration = $webpConfiguration;
    }

    /**
     * {@inheritdoc}
     */
    public function getFileNames(File $file): array
    {
        $fileNames = $this->innerFileNamesProvider->getFileNames($file);

        if ($this->webpConfiguration->isEnabledIfSupported()) {
            $webpFileNames = [];
            foreach ($fileNames as $fileName) {
                if (pathinfo($fileName, PATHINFO_EXTENSION) !== 'webp') {
                    $webpFileNames[] = $fileName . '.webp';
                }
            }

            $fileNames = array_merge($fileNames, $webpFileNames);
        }

        return $fileNames;
    }
}
