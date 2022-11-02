<?php

namespace Oro\Bundle\AttachmentBundle\Provider;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Tools\FilenameExtensionHelper;
use Oro\Bundle\AttachmentBundle\Tools\FilenameSanitizer;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;

/**
 * Uses a sanitized original filename for files if specified features are enabled.
 */
abstract class AbstractHumanReadableFileNameProvider implements FileNameProviderInterface
{
    use FeatureCheckerHolderTrait;

    protected const ORIGINAL_FILE_NAME_SEPARATOR = '-';

    protected FileNameProviderInterface $innerProvider;

    private FilenameExtensionHelper $filenameExtensionHelper;

    public function __construct(
        FileNameProviderInterface $innerProvider,
        FilenameExtensionHelper $filenameExtensionHelper
    ) {
        $this->innerProvider = $innerProvider;
        $this->filenameExtensionHelper = $filenameExtensionHelper;
    }

    public function getFileName(File $file): string
    {
        if (!$this->isApplicable($file)) {
            return $this->innerProvider->getFileName($file);
        }

        return $this->getNameWithFormat($file);
    }

    public function getFilteredImageName(File $file, string $filterName, string $format = ''): string
    {
        if (!$this->isApplicable($file)) {
            return $this->innerProvider->getFilteredImageName($file, $filterName, $format);
        }

        return $this->getNameWithFormat($file, $format);
    }

    public function getResizedImageName(File $file, int $width, int $height, string $format = ''): string
    {
        if (!$this->isApplicable($file)) {
            return $this->innerProvider->getResizedImageName($file, $width, $height, $format);
        }

        return $this->getNameWithFormat($file, $format);
    }

    private function getNameWithFormat(File $file, string $format = ''): string
    {
        if ($file->getOriginalFilename() === $file->getFilename()) {
            $filename = $file->getFilename();
        } else {
            $extension = $file->getExtension() ?? pathinfo($file->getFilename(), PATHINFO_EXTENSION);
            $hash = str_replace(
                '.' . $extension,
                '',
                $file->getFilename()
            );
            $filename = $hash . self::ORIGINAL_FILE_NAME_SEPARATOR . $file->getOriginalFilename();
        }

        $filename = $this->filenameExtensionHelper->addExtension($filename, $format, [$file->getMimeType()]);

        return FilenameSanitizer::sanitizeFilename($filename);
    }

    abstract protected function isApplicable(File $file): bool;
}
