<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Twig;

use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\AttachmentBundle\Provider\FileTitleProviderInterface;
use Oro\Bundle\AttachmentBundle\Provider\FileUrlProviderInterface;
use Oro\Bundle\AttachmentBundle\Provider\PictureSourcesProvider;
use Oro\Bundle\AttachmentBundle\Provider\PictureSourcesProviderInterface;
use Oro\Bundle\AttachmentBundle\Tests\Unit\Fixtures\TestFile;
use Oro\Bundle\AttachmentBundle\Twig\FileExtension;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class FileExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    private const FILE_ID = 42;

    private AttachmentManager|\PHPUnit\Framework\MockObject\MockObject $attachmentManager;

    private PictureSourcesProviderInterface|\PHPUnit\Framework\MockObject\MockObject
        $pictureSourcesProvider;

    private ConfigManager|\PHPUnit\Framework\MockObject\MockObject $configManager;

    private FileTitleProviderInterface|\PHPUnit\Framework\MockObject\MockObject $fileTitleProvider;

    private FileExtension $extension;

    private TestFile $file;

    protected function setUp(): void
    {
        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $this->attachmentManager = $this->createMock(AttachmentManager::class);
        $this->pictureSourcesProvider = $this->createMock(PictureSourcesProviderInterface::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->fileTitleProvider = $this->createMock(FileTitleProviderInterface::class);

        $serviceLocator = self::getContainerBuilder()
            ->add(AttachmentManager::class, $this->attachmentManager)
            ->add(PictureSourcesProvider::class, $this->pictureSourcesProvider)
            ->add(ConfigManager::class, $this->configManager)
            ->add(ManagerRegistry::class, $managerRegistry)
            ->add(FileTitleProviderInterface::class, $this->fileTitleProvider)
            ->getContainer($this);

        $this->extension = new FileExtension($serviceLocator);

        $this->file = new TestFile();
        $this->file->setId(self::FILE_ID);
        $this->file->setFilename('name.pdf');
        $this->file->setOriginalFilename('original-name.pdf');

        $fileRepo = $this->createMock(EntityRepository::class);
        $managerRegistry
            ->expects(self::any())
            ->method('getRepository')
            ->willReturn($fileRepo);
        $fileRepo
            ->expects(self::any())
            ->method('find')
            ->with($this->file->getId())
            ->willReturn($this->file);

        $this->attachmentManager
            ->expects(self::any())
            ->method('getFilteredImageUrl')
            ->willReturnCallback(static function (File $file, string $filter, string $format) {
                return '/' . $filter . '/' . $file->getFilename() . ($format ? '.' . $format : '');
            });

        $this->attachmentManager
            ->expects(self::any())
            ->method('getResizedImageUrl')
            ->willReturnCallback(static function (File $file, int $width, int $height, string $format) {
                return '/' . $width . '/' . $height . '/' . $file->getFilename() . ($format ? '.' . $format : '');
            });
    }

    public function testGetFileUrl(): void
    {
        $action = FileUrlProviderInterface::FILE_ACTION_DOWNLOAD;

        $this->attachmentManager->expects(self::once())
            ->method('getFileUrl')
            ->with($this->file, $action, UrlGeneratorInterface::ABSOLUTE_PATH);

        self::callTwigFunction(
            $this->extension,
            'file_url',
            [$this->file, $action, true]
        );
    }

    public function testGetResizedImageUrl(): void
    {
        $width = 110;
        $height = 120;

        $this->attachmentManager->expects(self::once())
            ->method('getResizedImageUrl')
            ->with($this->file, $width, $height);

        self::callTwigFunction($this->extension, 'resized_image_url', [$this->file, $width, $height]);
    }

    public function testGetFilteredImageUrl(): void
    {
        $result = self::callTwigFunction($this->extension, 'filtered_image_url', [$this->file, 'sample_filter']);

        self::assertEquals('/sample_filter/' . $this->file->getFilename(), $result);
    }

    public function testGetAttachmentIcon(): void
    {
        $this->attachmentManager->expects(self::once())
            ->method('getAttachmentIconClass')
            ->with($this->file);

        self::callTwigFunction($this->extension, 'oro_attachment_icon', [$this->file]);
    }

    public function testGetFileView(): void
    {
        $this->file->setMimeType('plain/text');
        $url = '/url/name.pdf';

        $environment = $this->createMock(Environment::class);
        $environment->expects(self::once())
            ->method('render')
            ->with(
                '@OroAttachment/Twig/file.html.twig',
                [
                    'iconClass' => '',
                    'url' => $url,
                    'pictureSources' => [],
                    'fileName' => 'original-name.pdf',
                    'additional' => null,
                    'title' => '',
                ]
            );

        $this->attachmentManager->expects(self::once())
            ->method('isImageType')
            ->with($this->file->getMimeType())
            ->willReturn(false);

        $this->attachmentManager->expects(self::once())
            ->method('getAttachmentIconClass')
            ->with($this->file);


        $this->attachmentManager->expects(self::once())
            ->method('getFileUrl')
            ->with($this->file)
            ->willReturn($url);

        self::callTwigFunction(
            $this->extension,
            'oro_file_view',
            [$environment, $this->file]
        );
    }

    public function testGetFileViewWhenImageType(): void
    {
        $this->file->setFilename('image.png');
        $this->file->setMimeType('image/png');
        $url = '/url/image.png';

        $environment = $this->createMock(Environment::class);
        $environment->expects(self::once())
            ->method('render')
            ->with(
                '@OroAttachment/Twig/file.html.twig',
                [
                    'iconClass' => '',
                    'url' => $url,
                    'pictureSources' => [
                        'src' => '/original/image.png',
                        'sources' => [],
                    ],
                    'fileName' => 'original-name.pdf',
                    'additional' => null,
                    'title' => '',
                ]
            );

        $this->attachmentManager->expects(self::once())
            ->method('isImageType')
            ->with($this->file->getMimeType())
            ->willReturn(true);

        $this->attachmentManager->expects(self::once())
            ->method('getAttachmentIconClass')
            ->with($this->file);

        $this->attachmentManager->expects(self::once())
            ->method('getFileUrl')
            ->with($this->file)
            ->willReturn($url);

        $this->pictureSourcesProvider->expects(self::once())
            ->method('getFilteredPictureSources')
            ->with($this->file)
            ->willReturn([
                'src' => '/original/image.png',
                'sources' => [],
            ]);

        self::callTwigFunction(
            $this->extension,
            'oro_file_view',
            [$environment, $this->file]
        );
    }

    public function testGetFileViewWhenImageTypeAndWebpIsSupported(): void
    {
        $this->file->setFilename('image.png');
        $this->file->setMimeType('image/png');
        $url = '/url/image.png';

        $environment = $this->createMock(Environment::class);
        $environment->expects(self::once())
            ->method('render')
            ->with(
                '@OroAttachment/Twig/file.html.twig',
                [
                    'iconClass' => '',
                    'url' => $url,
                    'pictureSources' => [
                        'src' => '/original/image.png',
                        'sources' => [
                            [
                                'srcset' => '/original/image.png.webp',
                                'type' => 'image/webp',
                            ],
                        ],
                    ],
                    'fileName' => 'original-name.pdf',
                    'additional' => null,
                    'title' => '',
                ]
            );

        $this->attachmentManager->expects(self::once())
            ->method('isImageType')
            ->with($this->file->getMimeType())
            ->willReturn(true);

        $this->attachmentManager->expects(self::once())
            ->method('getAttachmentIconClass')
            ->with($this->file);

        $this->attachmentManager->expects(self::once())
            ->method('getFileUrl')
            ->with($this->file)
            ->willReturn($url);

        $this->pictureSourcesProvider->expects(self::once())
            ->method('getFilteredPictureSources')
            ->with($this->file)
            ->willReturn([
                'src' => '/original/image.png',
                'sources' => [
                    [
                        'srcset' => '/original/image.png.webp',
                        'type' => 'image/webp',
                    ],
                ],
            ]);

        self::callTwigFunction(
            $this->extension,
            'oro_file_view',
            [$environment, $this->file]
        );
    }

    public function testGetEmptyImageView(): void
    {
        self::assertEquals(
            '',
            self::callTwigFunction(
                $this->extension,
                'oro_image_view',
                [$this->createMock(Environment::class), null]
            )
        );
    }

    public function testGetImageView(): void
    {
        $environment = $this->createTwigEnvironmentForImagesTemplate($this->file);

        self::callTwigFunction($this->extension, 'oro_image_view', [$environment, $this->file]);
    }

    public function testGetImageViewWithIntegerAttachmentParameter(): void
    {
        $environment = $this->createTwigEnvironmentForImagesTemplate($this->file);

        self::callTwigFunction($this->extension, 'oro_image_view', [$environment, self::FILE_ID]);
    }

    public function testGetImageViewConfigured(): void
    {
        $this->file->setFilename('test.doc');
        $this->file->setParentEntityClass($parentEntityClass = \stdClass::class);
        $this->file->setParentEntityFieldName($fieldName = 'sampleField');

        $config = $this->createMock(Config::class);
        $size = 120;

        $environment = $this->createTwigEnvironmentForImagesTemplate($this->file, $size, $size);

        $this->configManager->expects(self::once())
            ->method('getFieldConfig')
            ->with('attachment', $parentEntityClass, $fieldName)
            ->willReturn($config);

        $config->expects(self::exactly(2))
            ->method('get')
            ->willReturn($size);

        self::callTwigFunction(
            $this->extension,
            'oro_image_view',
            [$environment, $this->file]
        );
    }

    public function testGetImageViewConfiguredWithDefaultWidthAndHeight(): void
    {
        $this->file->setFilename('test.doc');
        $this->file->setParentEntityClass($parentEntityClass = \stdClass::class);
        $this->file->setParentEntityFieldName($fieldName = 'sampleField');

        $config = $this->createMock(Config::class);

        $environment = $this->createTwigEnvironmentForImagesTemplate($this->file);

        $this->configManager->expects(self::once())
            ->method('getFieldConfig')
            ->with('attachment', $parentEntityClass, $fieldName)
            ->willReturn($config);

        self::callTwigFunction(
            $this->extension,
            'oro_image_view',
            [$environment, $this->file]
        );
    }

    private function createTwigEnvironmentForImagesTemplate(
        File $file,
        int $width = 16,
        int $height = 16
    ): Environment|\PHPUnit\Framework\MockObject\MockObject {
        $environment = $this->createMock(Environment::class);
        $environment->expects(self::once())
            ->method('render')
            ->with(
                '@OroAttachment/Twig/image.html.twig',
                [
                    'file' => $file,
                    'width' => $width,
                    'height' => $height,
                    'fileName' => 'original-name.pdf',
                ]
            );

        return $environment;
    }

    public function testGetTypeIsImage(): void
    {
        $mimeType = 'image/jpeg';

        $this->attachmentManager->expects(self::once())
            ->method('isImageType')
            ->with($mimeType);

        self::callTwigFunction($this->extension, 'oro_type_is_image', [$mimeType]);
    }

    public function testIsPreviewAvailable(): void
    {
        $mimeType = 'image/jpeg';

        $this->attachmentManager->expects(self::once())
            ->method('isImageType')
            ->with($mimeType);

        self::callTwigFunction($this->extension, 'oro_is_preview_available', [$mimeType]);
    }

    public function testGetFileIconsConfig(): void
    {
        $this->attachmentManager->expects(self::once())
            ->method('getFileIcons');

        self::callTwigFunction($this->extension, 'oro_file_icons_config', []);
    }

    public function testGetTitle(): void
    {
        $localization = $this->createMock(Localization::class);

        $this->fileTitleProvider->expects(self::once())
            ->method('getTitle')
            ->with($this->file, $localization);

        self::callTwigFunction($this->extension, 'oro_file_title', [$this->file, $localization]);
    }

    public function testGetTitleWhenNullFile(): void
    {
        $this->fileTitleProvider->expects(self::never())
            ->method('getTitle');

        self::callTwigFunction($this->extension, 'oro_file_title', [null]);
    }

    public function testGetTitleWhenNoLocalization(): void
    {
        $this->fileTitleProvider->expects(self::once())
            ->method('getTitle')
            ->with($this->file);

        self::callTwigFunction($this->extension, 'oro_file_title', [$this->file]);
    }

    public function testGetFilteredPictureSourcesReturnsEmptyArrayWhenFileIsNull(): void
    {
        $result = self::callTwigFunction(
            $this->extension,
            'oro_filtered_picture_sources',
            [null]
        );

        self::assertEquals([], $result);
    }

    public function testGetFilteredPictureSources(): void
    {
        $this->file->setFilename('image.mime');
        $this->file->setExtension('mime');
        $this->file->setMimeType('image/mime');
        $filterName = 'sample_filter';

        $this->pictureSourcesProvider->expects(self::once())
            ->method('getFilteredPictureSources')
            ->with($this->file)
            ->willReturn([
                'src' => '/original/image.mime',
                'sources' => [
                    [
                        'srcset' => '/original/image.mime.webp',
                        'type' => 'image/webp',
                    ],
                ],
            ]);

        $result = self::callTwigFunction(
            $this->extension,
            'oro_filtered_picture_sources',
            [$this->file, $filterName, ['sample_key' => 'sample_value']]
        );

        self::assertEquals(
            [
                'src' => '/original/image.mime',
                'sources' => [
                    [
                        'srcset' => '/original/image.mime.webp',
                        'type' => 'image/webp',
                        'sample_key' => 'sample_value',
                    ],
                ],
            ],
            $result
        );
    }

    public function testGetFilteredPictureSourcesWhenFileIsInt(): void
    {
        $this->file->setFilename('image.mime');
        $this->file->setExtension('mime');
        $this->file->setMimeType('image/mime');
        $filterName = 'sample_filter';

        $this->pictureSourcesProvider->expects(self::once())
            ->method('getFilteredPictureSources')
            ->with($this->file)
            ->willReturn([
                'src' => '/original/image.mime',
                'sources' => [
                    [
                        'srcset' => '/original/image.mime.webp',
                        'type' => 'image/webp',
                    ],
                ],
            ]);

        $result = self::callTwigFunction(
            $this->extension,
            'oro_filtered_picture_sources',
            [self::FILE_ID, $filterName, ['sample_key' => 'sample_value']]
        );

        self::assertEquals(
            [
                'src' => '/original/image.mime',
                'sources' => [
                    [
                        'srcset' => '/original/image.mime.webp',
                        'type' => 'image/webp',
                        'sample_key' => 'sample_value',
                    ],
                ],
            ],
            $result
        );
    }

    public function testGetResizedPictureSourcesReturnsEmptyArrayWhenFileIsNull(): void
    {
        $result = self::callTwigFunction(
            $this->extension,
            'oro_resized_picture_sources',
            [null]
        );

        self::assertEquals([], $result);
    }

    public function testGetResizedPictureSources(): void
    {
        $this->file->setFilename('image.mime');
        $this->file->setExtension('mime');
        $this->file->setMimeType('image/mime');
        $width = 42;
        $height = 24;

        $this->pictureSourcesProvider->expects(self::once())
            ->method('getResizedPictureSources')
            ->with($this->file)
            ->willReturn([
                'src' => '/42/24/image.mime',
                'sources' => [
                    [
                        'srcset' => '/42/24/image.mime.webp',
                        'type' => 'image/webp',
                    ],
                ],
            ]);

        $result = self::callTwigFunction(
            $this->extension,
            'oro_resized_picture_sources',
            [$this->file, $width, $height, ['sample_key' => 'sample_value']]
        );

        self::assertEquals(
            [
                [
                    'srcset' => '/42/24/image.mime.webp',
                    'type' => 'image/webp',
                    'sample_key' => 'sample_value',
                ],
                [
                    'srcset' => '/42/24/image.mime',
                    'type' => 'image/mime',
                    'sample_key' => 'sample_value',
                ],
            ],
            $result
        );
    }

    public function testGetResizedPictureSourcesWhenFileIsInt(): void
    {
        $this->file->setFilename('image.mime');
        $this->file->setExtension('mime');
        $this->file->setMimeType('image/mime');
        $width = 42;
        $height = 24;

        $this->pictureSourcesProvider->expects(self::once())
            ->method('getResizedPictureSources')
            ->with($this->file)
            ->willReturn([
                'src' => '/42/24/image.mime',
                'sources' => [
                    [
                        'srcset' => '/42/24/image.mime.webp',
                        'type' => 'image/webp',
                    ],
                ],
            ]);

        $result = self::callTwigFunction(
            $this->extension,
            'oro_resized_picture_sources',
            [self::FILE_ID, $width, $height, ['sample_key' => 'sample_value']]
        );

        self::assertEquals(
            [
                [
                    'srcset' => '/42/24/image.mime.webp',
                    'type' => 'image/webp',
                    'sample_key' => 'sample_value',
                ],
                [
                    'srcset' => '/42/24/image.mime',
                    'type' => 'image/mime',
                    'sample_key' => 'sample_value',
                ],
            ],
            $result
        );
    }
}
