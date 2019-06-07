<?php

namespace Oro\Bundle\AttachmentBundle\Twig;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Entity\FileExtensionInterface;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Security\Acl\Util\ClassUtils;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides Twig functions to work with files, images and attachments:
 *   - file_url
 *   - file_size
 *   - resized_image_url
 *   - filtered_image_url
 *   - oro_configured_image_url
 *   - oro_attachment_icon
 *   - oro_type_is_image
 *   - oro_is_preview_available
 *   - oro_file_icons_config
 *   - oro_file_view
 *   - oro_image_view
 */
class FileExtension extends AbstractExtension
{
    const DEFAULT_THUMB_SIZE = 16;

    const FILES_TEMPLATE  = 'OroAttachmentBundle:Twig:file.html.twig';
    const IMAGES_TEMPLATE = 'OroAttachmentBundle:Twig:image.html.twig';

    /** @var ContainerInterface */
    protected $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return AttachmentManager
     */
    protected function getAttachmentManager()
    {
        return $this->container->get('oro_attachment.manager');
    }

    /**
     * @return ConfigManager
     */
    protected function getConfigManager()
    {
        return $this->container->get('oro_entity_config.config_manager');
    }

    /**
     * @return ManagerRegistry
     */
    protected function getDoctrine()
    {
        return $this->container->get('doctrine');
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('file_url', [$this, 'getFileUrl']),
            new TwigFunction('file_size', [$this, 'getFileSize']),
            new TwigFunction('resized_image_url', [$this, 'getResizedImageUrl']),
            new TwigFunction('filtered_image_url', [$this, 'getFilteredImageUrl']),
            new TwigFunction('oro_configured_image_url', [$this, 'getConfiguredImageUrl']),
            new TwigFunction('oro_attachment_icon', [$this, 'getAttachmentIcon']),
            new TwigFunction('oro_type_is_image', [$this, 'getTypeIsImage']),
            new TwigFunction('oro_is_preview_available', [$this, 'isPreviewAvailable']),
            new TwigFunction('oro_file_icons_config', [$this, 'getFileIconsConfig']),
            new TwigFunction(
                'oro_file_view',
                [$this, 'getFileView'],
                ['is_safe' => ['html'], 'needs_environment' => true]
            ),
            new TwigFunction(
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
        return $this->getAttachmentManager()->getFileUrl($parentEntity, $fieldName, $attachment, $type, $absolute);
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
        return $this->getAttachmentManager()->getFileSize($bytes);
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
        return $this->getAttachmentManager()->getResizedImageUrl($attachment, $width, $height);
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
        return $this->getAttachmentManager()->getAttachmentIconClass($attachment);
    }

    /**
     * Get file view html block
     *
     * @param Environment       $environment
     * @param mixed             $parentEntity
     * @param string            $fieldName
     * @param File              $attachment
     * @param array             $additional
     *
     * @return string
     */
    public function getFileView(
        Environment $environment,
        $parentEntity,
        $fieldName,
        $attachment = null,
        $additional = null
    ) {
        if (filter_var($attachment, FILTER_VALIDATE_INT)) {
            $attachment = $this->getFileById($attachment);
        }
        if ($attachment && $attachment->getFilename()) {
            $attachmentManager = $this->getAttachmentManager();
            $url = null;
            if (is_object($parentEntity)) {
                $url = $attachmentManager->getFileUrl($parentEntity, $fieldName, $attachment, 'download', true);
            }

            return $environment->loadTemplate(self::FILES_TEMPLATE)->render(
                [
                    'iconClass'  => $attachmentManager->getAttachmentIconClass($attachment),
                    'url'        => $url,
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
     * @param Environment       $environment
     * @param object            $parentEntity
     * @param mixed             $attachment
     * @param string|object     $entityClass
     * @param string            $fieldName
     *
     * @return string
     */
    public function getImageView(
        Environment $environment,
        $parentEntity,
        $attachment = null,
        $entityClass = null,
        $fieldName = ''
    ) {
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
                $config = $this->getConfigManager()
                    ->getProvider('attachment')
                    ->getConfig($entityClass, $fieldName);
                $width  = $config->get('width');
                $height = $config->get('height');
            }

            $attachmentManager = $this->getAttachmentManager();

            return $environment->loadTemplate(self::IMAGES_TEMPLATE)->render(
                [
                    'imagePath' => $attachmentManager->getResizedImageUrl($attachment, $width, $height),
                    'url'       => $attachmentManager
                        ->getFileUrl($parentEntity, $fieldName, $attachment, 'download', true),
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
            $config = $this->getConfigManager()->getProvider('attachment')->getConfig($entityClass, $fieldName);

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
        return $this->getAttachmentManager()->getFilteredImageUrl($attachment, $filterName);
    }

    /**
     * Checks if file type is an image
     *
     * @param  string $type
     * @return bool
     */
    public function getTypeIsImage($type)
    {
        return $this->getAttachmentManager()->isImageType($type);
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
        return $this->getAttachmentManager()->getFileIcons();
    }

    /**
     * @param int $id
     *
     * @return File
     */
    protected function getFileById($id)
    {
        return $this->getDoctrine()->getRepository('OroAttachmentBundle:File')->find($id);
    }
}
