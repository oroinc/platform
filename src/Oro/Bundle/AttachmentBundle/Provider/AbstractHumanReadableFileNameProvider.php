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

    public function __construct(FileNameProviderInterface $innerProvider)
    {
        $this->innerProvider = $innerProvider;
    }

    public function getFileName(File $file): string
    {
        if (!$this->isApplicable($file)) {
            return $this->innerProvider->getFileName($file);
        }

        return $this->getNameWithFormat($file);
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

        $filename = FilenameExtensionHelper::addExtension($filename, $format);

        return FilenameSanitizer::sanitizeFilename($filename);
    }

    abstract protected function isApplicable(File $file): bool;
}
