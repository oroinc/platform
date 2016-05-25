<?php

namespace Oro\Bundle\AttachmentBundle\Twig;

use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Security\Core\Util\ClassUtils;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Entity\FileExtensionInterface;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

class FileExtension extends \Twig_Extension
{
    const DEFAULT_THUMB_SIZE = 16;

    const FILES_TEMPLATE  = 'OroAttachmentBundle:Twig:file.html.twig';
    const IMAGES_TEMPLATE = 'OroAttachmentBundle:Twig:image.html.twig';

    /** @var AttachmentManager */
    protected $manager;

    /** @var ConfigProvider */
    protected $attachmentConfigProvider;

    /** @var ManagerRegistry */
    protected $doctrine;

    /**
     * @param AttachmentManager $manager
     * @param ConfigManager     $configManager
     */
    public function __construct(AttachmentManager $manager, ConfigManager $configManager, ManagerRegistry $doctrine)
    {
        $this->manager                  = $manager;
        $this->attachmentConfigProvider = $configManager->getProvider('attachment');
        $this->doctrine                 = $doctrine;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('file_url', [$this, 'getFIleUrl']),
            new \Twig_SimpleFunction('file_size', [$this, 'getFIleSize']),
            new \Twig_SimpleFunction('resized_image_url', [$this, 'getResizedImageUrl']),
            new \Twig_SimpleFunction('filtered_image_url', [$this, 'getFilteredImageUrl']),
            new \Twig_SimpleFunction('oro_configured_image_url', [$this, 'getConfiguredImageUrl']),
            new \Twig_SimpleFunction('oro_attachment_icon', [$this, 'getAttachmentIcon']),
            new \Twig_SimpleFunction('oro_type_is_image', [$this, 'getTypeIsImage']),
            new \Twig_SimpleFunction('oro_is_preview_available', [$this, 'isPreviewAvailable']),
            new \Twig_SimpleFunction('oro_file_icons_config', [$this, 'getFileIconsConfig']),
            new \Twig_SimpleFunction(
                'oro_file_view',
                [$this, 'getFileView'],
                ['is_safe' => ['html'], 'needs_environment' => true]
            ),
            new \Twig_SimpleFunction(
                'oro_image_view',
                [$this, 'getImageView'],
                ['is_safe' => ['html'], 'needs_environment' => true]
            )
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_attachment_file';
    }

    /**
     * Get file url
     *
     * @param object $parentEntity
     * @param string $fieldName
     * @param File   $attachment
     * @param string $type
     * @param bool   $absolute
     *
     * @return string
     */
    public function getFileUrl(
        $parentEntity,
        $fieldName,
        File $attachment,
        $type = 'get',
        $absolute = false
    ) {
        return $this->manager->getFileUrl($parentEntity, $fieldName, $attachment, $type, $absolute);
    }

    /**
     * Get human readable file size
     *
     * @param integer $bytes
     *
     * @return string
     */
    public function getFileSize($bytes)
    {
        return $this->manager->getFileSize($bytes);
    }

    /**
     * Get resized attachment image url
     *
     * @param File $attachment
     * @param int  $width
     * @param int  $height
     *
     * @return string
     */
    public function getResizedImageUrl(
        File $attachment,
        $width = self::DEFAULT_THUMB_SIZE,
        $height = self::DEFAULT_THUMB_SIZE
    ) {
        return $this->manager->getResizedImageUrl($attachment, $width, $height);
    }

    /**
     * Get attachment icon class
     *
     * @param FileExtensionInterface $attachment
     *
     * @return string
     */
    public function getAttachmentIcon(FileExtensionInterface $attachment)
    {
        return $this->manager->getAttachmentIconClass($attachment);
    }

    /**
     * Get file view html block
     *
     * @param \Twig_Environment $environment
     * @param object            $parentEntity
     * @param string            $fieldName
     * @param File              $attachment
     * @param array             $additional
     *
     * @return string
     */
    public function getFileView(
        \Twig_Environment $environment,
        $parentEntity,
        $fieldName,
        $attachment = null,
        $additional = null
    ) {
        /**
         * @todo: should be refactored in BAP-5637
         */
        if (filter_var($attachment, FILTER_VALIDATE_INT)) {
            $attachment = $this->getFileById($attachment);
        }
        if ($attachment && $attachment->getFilename()) {
            return $environment->loadTemplate(self::FILES_TEMPLATE)->render(
                [
                    'iconClass'  => $this->manager->getAttachmentIconClass($attachment),
                    'url'        => $this->manager
                        ->getFileUrl($parentEntity, $fieldName, $attachment, 'download', true),
                    'fileName'   => $attachment->getOriginalFilename(),
                    'additional' => $additional
                ]
            );
        }

        return '';
    }

    /**
     * Get Image html block
     *
     * @param \Twig_Environment $environment
     * @param object            $parentEntity
     * @param mixed             $attachment
     * @param string|object     $entityClass
     * @param string            $fieldName
     *
     * @return string
     */
    public function getImageView(
        \Twig_Environment $environment,
        $parentEntity,
        $attachment = null,
        $entityClass = null,
        $fieldName = ''
    ) {
        /**
         * @todo: should be refactored in BAP-5637
         */
        if (filter_var($attachment, FILTER_VALIDATE_INT)) {
            $attachment = $this->getFileById($attachment);
        }

        if ($attachment && $attachment->getFilename()) {
            $width  = self::DEFAULT_THUMB_SIZE;
            $height = self::DEFAULT_THUMB_SIZE;

            if ($entityClass && $fieldName) {
                if (is_object($entityClass)) {
                    $entityClass = ClassUtils::getRealClass($entityClass);
                }
                $config = $this->attachmentConfigProvider->getConfig($entityClass, $fieldName);
                $width  = $config->get('width');
                $height = $config->get('height');
            }
            return $environment->loadTemplate(self::IMAGES_TEMPLATE)->render(
                [
                    'imagePath' => $this->manager->getResizedImageUrl($attachment, $width, $height),
                    'url'       => $this->manager->getFileUrl($parentEntity, $fieldName, $attachment, 'download', true),
                    'fileName'  => $attachment->getOriginalFilename()
                ]
            );
        }

        return '';
    }

    /**
     * Get attachment image resized with config values
     *
     * @param object $parentEntity
     * @param string $fieldName
     * @param File   $attachment
     *
     * @return string
     */
    public function getConfiguredImageUrl($parentEntity, $fieldName, File $attachment = null)
    {
        if (!$attachment) {
            $attachment = PropertyAccess::createPropertyAccessor()->getValue($parentEntity, $fieldName);
        }

        if ($attachment && $attachment->getFilename()) {
            $entityClass = ClassUtils::getRealClass($parentEntity);
            $config      = $this->attachmentConfigProvider->getConfig($entityClass, $fieldName);

            return $this->getResizedImageUrl($attachment, $config->get('width'), $config->get('height'));
        }

        return '';
    }

    /**
     * @param File   $attachment
     * @param string $filterName
     *
     * @return string
     */
    public function getFilteredImageUrl(File $attachment, $filterName)
    {
        return $this->manager->getFilteredImageUrl($attachment, $filterName);
    }

    /**
     * Checks if file type is an image
     *
     * @param  string $type
     * @return bool
     */
    public function getTypeIsImage($type)
    {
        return $this->manager->isImageType($type);
    }

    /**
     * Check if we can show preview for file type
     * Currently only images preview is supported
     *
     * @param  string $type
     * @return bool
     */
    public function isPreviewAvailable($type)
    {
        return $this->getTypeIsImage($type);
    }

    /**
     * Get config array of file icons
     *
     * @return array
     */
    public function getFileIconsConfig()
    {
        return $this->manager->getFileIcons();
    }

    /**
     * @param int $id
     *
     * @return File
     */
    protected function getFileById($id)
    {
        return $this->doctrine->getRepository('OroAttachmentBundle:File')->find($id);
    }
}
