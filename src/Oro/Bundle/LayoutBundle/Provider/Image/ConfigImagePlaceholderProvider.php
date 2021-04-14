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
    /** @var ConfigManager */
    private $configManager;

    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var AttachmentManager */
    private $attachmentManager;

    /** @var string */
    private $configKey;

    /**
     * @param ConfigManager $configManager
     * @param DoctrineHelper $doctrineHelper
     * @param AttachmentManager $attachmentManager
     * @param string $configKey
     */
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

    /**
     * {@inheritdoc}
     */
    public function getPath(string $filter, int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH): ?string
    {
        $id = $this->configManager->get($this->configKey);
        if (!$id) {
            return null;
        }

        /** @var File $image */
        $image = $this->doctrineHelper->getEntity(File::class, $id);
        if (!$image) {
            return null;
        }

        return $this->attachmentManager->getFilteredImageUrl($image, $filter, $referenceType);
    }
}
