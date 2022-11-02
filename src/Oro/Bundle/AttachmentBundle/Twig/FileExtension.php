<?php

namespace Oro\Bundle\AttachmentBundle\Twig;

use Doctrine\Persistence\ManagerRegistry;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Entity\FileExtensionInterface;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\AttachmentBundle\Provider\FileTitleProviderInterface;
use Oro\Bundle\AttachmentBundle\Provider\FileUrlProviderInterface;
use Oro\Bundle\AttachmentBundle\Provider\PictureSourcesProvider;
use Oro\Bundle\AttachmentBundle\Provider\PictureSourcesProviderInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Component\PhpUtils\Formatter\BytesFormatter;
use Psr\Container\ContainerInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides Twig functions to work with files, images and attachments.
 */
class FileExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    private const DEFAULT_THUMB_SIZE = 16;
    private const FILES_TEMPLATE = '@OroAttachment/Twig/file.html.twig';
    private const IMAGES_TEMPLATE = '@OroAttachment/Twig/image.html.twig';

    private ContainerInterface $container;
    private ?AttachmentManager $attachmentManager = null;
    private ?PictureSourcesProviderInterface $pictureSourcesProvider = null;
    private ?ConfigManager $configManager = null;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('file_url', [$this, 'getFileUrl']),
            new TwigFunction('file_size', [$this, 'getFileSize']),
            new TwigFunction('resized_image_url', [$this, 'getResizedImageUrl']),
            new TwigFunction('filtered_image_url', [$this, 'getFilteredImageUrl']),
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
            new TwigFunction(
                'oro_resized_picture_sources',
                [$this, 'getResizedPictureSources']
            ),
            new TwigFunction(
                'oro_filtered_picture_sources',
                [$this, 'getFilteredPictureSources']
            ),
            new TwigFunction('oro_file_title', [$this, 'getFileTitle']),
        ];
    }

    public function getFileUrl(
        File $file,
        string $action = FileUrlProviderInterface::FILE_ACTION_GET,
        bool $absolute = false
    ): string {
        $referenceType = $absolute === false
            ? UrlGeneratorInterface::ABSOLUTE_URL
            : UrlGeneratorInterface::ABSOLUTE_PATH;

        return $this->getAttachmentManager()->getFileUrl($file, $action, $referenceType);
    }

    public function getResizedImageUrl(
        File $file,
        int $width = self::DEFAULT_THUMB_SIZE,
        int $height = self::DEFAULT_THUMB_SIZE,
        string $format = ''
    ): string {
        return $this->getAttachmentManager()->getResizedImageUrl($file, $width, $height, $format);
    }

    public function getFilteredImageUrl(File $file, string $filterName, string $format = ''): string
    {
        return $this->getAttachmentManager()->getFilteredImageUrl($file, $filterName, $format);
    }

    /**
     * Get human-readable file size
     */
    public function getFileSize(int|string|null $bytes): string
    {
        return BytesFormatter::format((int)$bytes);
    }

    /**
     * Get attachment icon class
     */
    public function getAttachmentIcon(FileExtensionInterface $attachment): string
    {
        return $this->getAttachmentManager()->getAttachmentIconClass($attachment);
    }

    /**
     * Get file view html block
     */
    public function getFileView(Environment $environment, File|int|null $file, ?array $additional = null): string
    {
        $file = $this->getFile($file);
        if (!$file || !$file->getFilename()) {
            return '';
        }

        $url = $this->getAttachmentManager()->getFileUrl(
            $file,
            FileUrlProviderInterface::FILE_ACTION_DOWNLOAD,
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $pictureSources = [];
        if ($this->getAttachmentManager()->isImageType($file->getMimeType())) {
            $pictureSources = $this->getFilteredPictureSources($file);
        }

        return $environment->render(
            self::FILES_TEMPLATE,
            [
                'iconClass' => $this->getAttachmentManager()->getAttachmentIconClass($file),
                'url' => $url,
                'pictureSources' => $pictureSources,
                'fileName' => $file->getOriginalFilename(),
                'additional' => $additional,
                'title' => $this->getFileTitle($file),
            ]
        );
    }

    /**
     * Get Image html block
     */
    public function getImageView(Environment $environment, File|int|null $file): string
    {
        $file = $this->getFile($file);
        if (!$file || !$file->getFilename()) {
            return '';
        }

        $width = self::DEFAULT_THUMB_SIZE;
        $height = self::DEFAULT_THUMB_SIZE;
        $entityClass = $file->getParentEntityClass();
        $fieldName = $file->getParentEntityFieldName();

        if ($entityClass && $fieldName) {
            $config = $this->getConfigManager()->getFieldConfig('attachment', $entityClass, $fieldName);
            $width = (int) $config->get('width') ?: self::DEFAULT_THUMB_SIZE;
            $height = (int) $config->get('height') ?: self::DEFAULT_THUMB_SIZE;
        }

        return $environment->render(
            self::IMAGES_TEMPLATE,
            [
                'file' => $file,
                'width' => $width,
                'height' => $height,
                'fileName' => $file->getOriginalFilename(),
            ]
        );
    }

    /**
     * Returns sources array that can be used in <picture> tag.
     * Adds WebP image variants is current oro_attachment.webp_strategy is "if_supported".
     *
     * @param File|int|null $file
     * @param int $width
     * @param int $height
     * @param array $attrs Extra attributes to add to <source> tags
     *
     * @return array
     *  [
     *      [
     *          'srcset' => '/url/for/image.png',
     *          'type' => 'image/png',
     *      ],
     *      // ...
     *  ]
     */
    public function getResizedPictureSources(
        File|int|null $file,
        int $width = self::DEFAULT_THUMB_SIZE,
        int $height = self::DEFAULT_THUMB_SIZE,
        array $attrs = []
    ): array {
        $file = $this->getFile($file);
        $sources = [];
        if ($file instanceof File) {
            $pictureSources = $this->getPictureSourcesProvider()->getResizedPictureSources($file, $width, $height);
            $sources = $this->mergeSources($pictureSources, $file, $attrs);
        }

        return $sources;
    }

    /**
     * Returns sources array that can be used in <picture> tag.
     * Adds WebP image variants is current oro_attachment.webp_strategy is "if_supported".
     *
     * @param File|int|null $file
     * @param string $filterName
     * @param array $attrs Extra attributes to add to <source> tags
     *
     * @return array
     *  [
     *      [
     *          'srcset' => '/url/for/image.png',
     *          'type' => 'image/png',
     *      ],
     *      // ...
     *  ]
     */
    public function getFilteredPictureSources(
        File|int|null $file,
        string $filterName = 'original',
        array $attrs = []
    ): array {
        $file = $this->getFile($file);
        $pictureSources = [];

        if ($file) {
            $pictureSources = $this->getPictureSourcesProvider()->getFilteredPictureSources($file, $filterName);

            $pictureSources['sources'] = array_map(
                static fn (array $source) => array_merge($source, $attrs),
                $pictureSources['sources'] ?? []
            );
        }

        return $pictureSources;
    }

    private function mergeSources(array $pictureSources, File $file, array $attrs): array
    {
        $sources = $pictureSources['sources'] ?? [];
        if (!empty($pictureSources['src'])) {
            $sources = array_merge(
                $pictureSources['sources'],
                [['srcset' => $pictureSources['src'], 'type' => $file->getMimeType()]]
            );
        }

        if ($attrs) {
            $sources = array_map(static fn (array $source) => array_merge($source, $attrs), $sources);
        }

        return $sources;
    }

    /**
     * Checks if file type is an image
     */
    public function getTypeIsImage(string $type): bool
    {
        return $this->getAttachmentManager()->isImageType($type);
    }

    /**
     * Check if we can show preview for file type
     * Currently only images preview is supported
     */
    public function isPreviewAvailable(string $type): bool
    {
        return $this->getTypeIsImage($type);
    }

    /**
     * Get config array of file icons
     */
    public function getFileIconsConfig(): array
    {
        return $this->getAttachmentManager()->getFileIcons();
    }

    /**
     * Provides file title which can be used, e.g. in title or alt HTML attributes.
     */
    public function getFileTitle(?File $file, Localization $localization = null): string
    {
        if (!$file) {
            return '';
        }

        return $this->container->get(FileTitleProviderInterface::class)->getTitle($file, $localization);
    }

    private function getFile(File|int|null $file): ?File
    {
        if (filter_var($file, FILTER_VALIDATE_INT)) {
            return $this->getDoctrine()->getRepository(File::class)->find($file);
        }

        return $file;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices(): array
    {
        return [
            AttachmentManager::class,
            PictureSourcesProvider::class,
            ConfigManager::class,
            ManagerRegistry::class,
            PropertyAccessorInterface::class,
            FileTitleProviderInterface::class,
            CacheManager::class,
        ];
    }

    private function getAttachmentManager(): AttachmentManager
    {
        if (null === $this->attachmentManager) {
            $this->attachmentManager = $this->container->get(AttachmentManager::class);
        }

        return $this->attachmentManager;
    }

    private function getPictureSourcesProvider(): PictureSourcesProviderInterface
    {
        if (null === $this->pictureSourcesProvider) {
            $this->pictureSourcesProvider = $this->container->get(PictureSourcesProvider::class);
        }

        return $this->pictureSourcesProvider;
    }

    private function getConfigManager(): ConfigManager
    {
        if (null === $this->configManager) {
            $this->configManager = $this->container->get(ConfigManager::class);
        }

        return $this->configManager;
    }

    private function getDoctrine(): ManagerRegistry
    {
        return $this->container->get(ManagerRegistry::class);
    }
}
