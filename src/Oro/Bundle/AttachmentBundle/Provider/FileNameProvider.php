<?php

namespace Oro\Bundle\AttachmentBundle\Provider;

use Liip\ImagineBundle\Imagine\Filter\FilterConfiguration;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Tools\FilenameExtensionHelper;
use Oro\Bundle\AttachmentBundle\Tools\FilenameSanitizer;

/**
 * Returns a filename for a specific File entity as is.
 */
class FileNameProvider implements FileNameProviderInterface
{
    private FilterConfiguration $filterConfiguration;

    private ?FilenameExtensionHelper $filenameExtensionHelper = null;

    public function __construct(FilterConfiguration $filterConfiguration)
    {
        $this->filterConfiguration = $filterConfiguration;
    }

    public function setFilenameExtensionHelper(FilenameExtensionHelper $filenameExtensionHelper): void
    {
        $this->filenameExtensionHelper = $filenameExtensionHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function getFileName(File $file): string
    {
        return (string) $file->getFilename();
    }

    /**
     * Uses format taken from LiipImagine filter config if it is not specified explicitly.
     *
     * {@inheritdoc}
     */
    public function getFilteredImageName(File $file, string $filterName, string $format = ''): string
    {
        if (!$format) {
            $format = $this->filterConfiguration->get($filterName)['format'] ?? '';
            if (!$format) {
                $format = (string) $file->getExtension();
            }
        }

        return $this->getNameWithFormat($file, $format);
    }

    public function getResizedImageName(File $file, int $width, int $height, string $format = ''): string
    {
        return $this->getNameWithFormat($file, $format);
    }

    private function getNameWithFormat(File $file, string $format): string
    {
        $filename = $this->filenameExtensionHelper
            ? $this->filenameExtensionHelper->addExtensionIfSupportedMimeTypes(
                (string) $file->getFilename(),
                $format,
                [$file->getMimeType()]
            )
            : FilenameExtensionHelper::addExtension((string) $file->getFilename(), $format);

        return FilenameSanitizer::sanitizeFilename($filename);
    }
}
