<?php

namespace Oro\Bundle\LayoutBundle\Provider\Image;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Provides the path to the image placeholder based on the parameter from the system configuration.
 */
class ConfigImagePlaceholderProvider implements ImagePlaceholderProviderInterface
{
    private ConfigManager $configManager;

    private DoctrineHelper $doctrineHelper;

    private AttachmentManager $attachmentManager;

    private string $configKey;

    public function __construct(
        ConfigManager $configManager,
        DoctrineHelper $doctrineHelper,
        AttachmentManager $attachmentManager,
        string $configKey
    ) {
        $this->configManager = $configManager;
        $this->doctrineHelper = $doctrineHelper;
        $this->attachmentManager = $attachmentManager;
        $this->configKey = $configKey;
    }

    public function getPath(
        string $filter,
        string $format = '',
        int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH
    ): ?string {
        $id = $this->configManager->get($this->configKey);
        if (!$id) {
            return null;
        }

        /** @var File $image */
        $image = $this->doctrineHelper->getEntity(File::class, $id);
        if (!$image) {
            return null;
        }

        return $this->attachmentManager->getFilteredImageUrl($image, $filter, $format, $referenceType);
    }
}
