<?php

namespace Oro\Bundle\DigitalAssetBundle\Provider;

use Oro\Bundle\AttachmentBundle\Tools\MimeTypesConverter;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;

/**
 * Provides a list of mime-types allowed for uploading as digital asset.
 */
class MimeTypesProvider
{
    /**
     * @var ConfigManager
     */
    private $configManager;

    /**
     * @param ConfigManager $configManager
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * @return array
     */
    public function getMimeTypes(): array
    {
        return array_unique(array_merge(
            MimeTypesConverter::convertToArray($this->configManager->get('oro_attachment.upload_file_mime_types', '')),
            MimeTypesConverter::convertToArray($this->configManager->get('oro_attachment.upload_image_mime_types', ''))
        ));
    }

    /**
     * @return array
     */
    public function getMimeTypesAsChoices(): array
    {
        $mimeTypes = $this->getMimeTypes();

        return array_combine($mimeTypes, $mimeTypes);
    }
}
