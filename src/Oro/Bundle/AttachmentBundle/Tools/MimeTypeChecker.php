<?php

namespace Oro\Bundle\AttachmentBundle\Tools;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

/**
 * Checks file type by MIME type.
 */
class MimeTypeChecker
{
    /** @var ConfigManager */
    private $configManager;

    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * Checks if the given mime type belongs to the image mime types.
     */
    public function isImageMimeType(string $mimeType): bool
    {
        $imageMimeTypes = MimeTypesConverter::convertToArray(
            $this->configManager->get('oro_attachment.upload_image_mime_types')
        );

        return \in_array($mimeType, $imageMimeTypes, false);
    }
}
