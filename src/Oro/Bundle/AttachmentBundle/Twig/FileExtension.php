<?php

namespace Oro\Bundle\AttachmentBundle\Twig;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Entity\FileExtensionInterface;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\AttachmentBundle\Provider\FileTitleProviderInterface;
use Oro\Bundle\AttachmentBundle\Provider\FileUrlProviderInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Component\PhpUtils\Formatter\BytesFormatter;
use Psr\Container\ContainerInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Acl\Util\ClassUtils;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
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
class FileExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    private const DEFAULT_THUMB_SIZE = 16;
    private const FILES_TEMPLATE = 'OroAttachmentBundle:Twig:file.html.twig';
    private const IMAGES_TEMPLATE = 'OroAttachmentBundle:Twig:image.html.twig';

    /** @var ContainerInterface */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return AttachmentManager
     */
    protected function getAttachmentManager()
    {
        return $this->container->get(AttachmentManager::class);
    }

    /**
     * @return ConfigManager
     */
    protected function getConfigManager()
    {
        return $this->container->get(ConfigManager::class);
    }

    /**
     * @return ManagerRegistry
     */
    protected function getDoctrine()
    {
        return $this->container->get(ManagerRegistry::class);
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
            ),
            new TwigFunction('oro_file_title', [$this, 'getFileTitle']),
        ];
    }

    /**
     * Get file url
     *
     * @param File $file
     * @param string $action
     * @param bool $absolute
     *
     * @return string
     */
    public function getFileUrl(
        File $file,
        string $action = FileUrlProviderInterface::FILE_ACTION_GET,
        bool $absolute = false
    ) {
        $referenceType = $absolute === false
            ? UrlGeneratorInterface::ABSOLUTE_URL
            : UrlGeneratorInterface::ABSOLUTE_PATH;

        return $this->getAttachmentManager()->getFileUrl($file, $action, $referenceType);
    }

    /**
     * Get resized attachment image url
     *
     * @param File $file
     * @param int  $width
     * @param int  $height
     *
     * @return string
     */
    public function getResizedImageUrl(
        File $file,
        $width = self::DEFAULT_THUMB_SIZE,
        $height = self::DEFAULT_THUMB_SIZE
    ) {
        return $this->getAttachmentManager()->getResizedImageUrl($file, $width, $height);
    }

    /**
     * @param File $file
     * @param string $filterName
     *
     * @return string
     */
    public function getFilteredImageUrl(File $file, $filterName)
    {
        return $this->getAttachmentManager()->getFilteredImageUrl($file, $filterName);
    }

    /**
     * Get attachment image resized with config values
     *
     * @param object $parentEntity
     * @param string $fieldName
     * @param File   $file
     *
     * @return string
     */
    public function getConfiguredImageUrl($parentEntity, $fieldName, File $file = null)
    {
        if (!$file) {
            $file = $this->getPropertyAccessor()->getValue($parentEntity, $fieldName);
        }

        if ($file && $file->getFilename()) {
            $entityClass = ClassUtils::getRealClass($parentEntity);
            $config = $this->getConfigManager()->getProvider('attachment')->getConfig($entityClass, $fieldName);

            return $this->getResizedImageUrl($file, $config->get('width'), $config->get('height'));
        }

        return '';
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
        return BytesFormatter::format($bytes);
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
     * @param Environment $environment
     * @param File|int|null $file
     * @param array $additional
     *
     * @return string
     */
    public function getFileView(Environment $environment, $file, ?array $additional = null)
    {
        if (filter_var($file, FILTER_VALIDATE_INT)) {
            $file = $this->getFileById($file);
        }

        if (!$file || !$file->getFilename()) {
            return '';
        }

        $url = $this->getAttachmentManager()->getFileUrl(
            $file,
            FileUrlProviderInterface::FILE_ACTION_DOWNLOAD,
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        return $environment->loadTemplate(self::FILES_TEMPLATE)->render(
            [
                'iconClass' => $this->getAttachmentManager()->getAttachmentIconClass($file),
                'url' => $url,
                'fileName' => $file->getOriginalFilename(),
                'additional' => $additional,
                'title' => $this->getFileTitle($file),
            ]
        );
    }

    /**
     * Get Image html block
     *
     * @param Environment $environment
     * @param File|int|null $file
     *
     * @return string
     */
    public function getImageView(Environment $environment, $file)
    {
        if (filter_var($file, FILTER_VALIDATE_INT)) {
            $file = $this->getFileById($file);
        }

        if (!$file || !$file->getFilename()) {
            return '';
        }

        $width = self::DEFAULT_THUMB_SIZE;
        $height = self::DEFAULT_THUMB_SIZE;
        $entityClass = $file->getParentEntityClass();
        $fieldName = $file->getParentEntityFieldName();

        if ($entityClass && $fieldName) {
            $config = $this->getConfigManager()->getProvider('attachment')->getConfig($entityClass, $fieldName);
            $width = $config->get('width');
            $height = $config->get('height');
        }

        return $environment->loadTemplate(self::IMAGES_TEMPLATE)->render(
            [
                'imagePath' => $this->getAttachmentManager()->getResizedImageUrl($file, $width, $height),
                'url' => $this->getAttachmentManager()->getFileUrl(
                    $file,
                    FileUrlProviderInterface::FILE_ACTION_DOWNLOAD,
                    UrlGeneratorInterface::ABSOLUTE_URL
                ),
                'fileName' => $file->getOriginalFilename(),
                'title' => $this->getFileTitle($file),
            ]
        );
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
     * Provides file title which can be used, e.g. in title or alt HTML attributes.
     *
     * @param File|null $file
     * @param Localization|null $localization
     *
     * @return string|null
     */
    public function getFileTitle(?File $file, Localization $localization = null): string
    {
        if (!$file) {
            return '';
        }

        return $this->container->get(FileTitleProviderInterface::class)->getTitle($file, $localization);
    }

    /**
     * @param int $id
     *
     * @return File
     */
    protected function getFileById($id)
    {
        return $this->getDoctrine()->getRepository(File::class)->find($id);
    }

    private function getPropertyAccessor(): PropertyAccessorInterface
    {
        return $this->container->get(PropertyAccessorInterface::class);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return [
            AttachmentManager::class,
            ConfigManager::class,
            ManagerRegistry::class,
            PropertyAccessorInterface::class,
            FileTitleProviderInterface::class,
        ];
    }
}
