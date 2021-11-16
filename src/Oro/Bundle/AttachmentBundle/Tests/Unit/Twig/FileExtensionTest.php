<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Twig;

use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\AttachmentBundle\Provider\FileTitleProviderInterface;
use Oro\Bundle\AttachmentBundle\Provider\FileUrlProviderInterface;
use Oro\Bundle\AttachmentBundle\Tests\Unit\Fixtures\TestFile;
use Oro\Bundle\AttachmentBundle\Tests\Unit\Stub\ParentEntity;
use Oro\Bundle\AttachmentBundle\Twig\FileExtension;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class FileExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var FileExtension */
    private $extension;

    /** @var AttachmentManager|\PHPUnit\Framework\MockObject\MockObject */
    private $attachmentManager;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var PropertyAccessorInterface */
    private $propertyAccessor;

    /** @var FileTitleProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $fileTitleProvider;

    /** @var TestFile */
    private $file;

    protected function setUp(): void
    {
        $this->attachmentManager = $this->createMock(AttachmentManager::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->propertyAccessor = $this->createMock(PropertyAccessorInterface::class);
        $this->fileTitleProvider = $this->createMock(FileTitleProviderInterface::class);

        $serviceLocator = self::getContainerBuilder()
            ->add(AttachmentManager::class, $this->attachmentManager)
            ->add(ConfigManager::class, $this->configManager)
            ->add(ManagerRegistry::class, $this->doctrine)
            ->add(PropertyAccessorInterface::class, $this->propertyAccessor)
            ->add(FileTitleProviderInterface::class, $this->fileTitleProvider)
            ->getContainer($this);

        $this->extension = new FileExtension($serviceLocator);

        $this->file = new TestFile();
        $this->file->setFilename('test.txt');
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
        $filter = 'testFilter';

        $this->attachmentManager->expects(self::once())
            ->method('getFilteredImageUrl')
            ->with($this->file, $filter);

        self::callTwigFunction($this->extension, 'filtered_image_url', [$this->file, $filter]);
    }

    public function testGetConfiguredImageUrl(): void
    {
        $config = $this->createMock(Config::class);

        $this->configManager->expects(self::once())
            ->method('getFieldConfig')
            ->with('attachment', ParentEntity::class, 'testField')
            ->willReturn($config);

        $config->expects(self::exactly(2))
            ->method('get')
            ->willReturn(45);

        $this->file->setFilename('test.doc');

        $this->attachmentManager->expects(self::once())
            ->method('getResizedImageUrl')
            ->with($this->file, 45, 45);

        $this->propertyAccessor->expects(self::never())
            ->method('getValue');

        self::callTwigFunction(
            $this->extension,
            'oro_configured_image_url',
            [new ParentEntity(1, null, []), 'testField', $this->file]
        );
    }

    public function testGetConfiguredImageUrlWhenFileFromParentEntity(): void
    {
        $file = new File();
        $fieldName = 'file';
        $parentEntity = new ParentEntity(1, $file, []);
        $config = $this->createMock(Config::class);

        $this->configManager->expects(self::once())
            ->method('getFieldConfig')
            ->with('attachment', ParentEntity::class, 'file')
            ->willReturn($config);

        $config->expects(self::exactly(2))
            ->method('get')
            ->willReturn(45);

        $this->attachmentManager->expects(self::once())
            ->method('getResizedImageUrl')
            ->with($file, 45, 45);

        $file->setFilename('test.doc');

        $this->propertyAccessor->expects(self::once())
            ->method('getValue')
            ->with($parentEntity, $fieldName)
            ->willReturn($file);

        self::callTwigFunction(
            $this->extension,
            'oro_configured_image_url',
            [$parentEntity, $fieldName]
        );
    }

    public function testGetConfiguredImageUrlWhenNoFile(): void
    {
        $this->configManager->expects(self::never())
            ->method('getFieldConfig');

        $this->attachmentManager->expects(self::never())
            ->method('getResizedImageUrl');

        self::callTwigFunction(
            $this->extension,
            'oro_configured_image_url',
            [new ParentEntity(1, null, []), 'file']
        );
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
        $this->file->setFilename('test.doc');

        $environment = $this->createMock(Environment::class);
        $environment->expects(self::once())
            ->method('render')
            ->with(
                '@OroAttachment/Twig/file.html.twig',
                [
                    'iconClass' => '',
                    'url' => '',
                    'fileName' => null,
                    'additional' => null,
                    'title' => '',
                ]
            );

        $this->attachmentManager->expects(self::once())
            ->method('getAttachmentIconClass')
            ->with($this->file);

        $this->attachmentManager->expects(self::once())
            ->method('getFileUrl');

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
        $this->file->setFilename('test.doc');

        $environment = $this->createTwigEnvironmentForImagesTemplate();

        $this->attachmentManager->expects(self::once())
            ->method('getResizedImageUrl')
            ->with($this->file, 16, 16);

        $this->attachmentManager->expects(self::once())
            ->method('getFileUrl');

        self::callTwigFunction($this->extension, 'oro_image_view', [$environment, $this->file]);
    }

    public function testGetImageViewWithIntegerAttachmentParameter(): void
    {
        $this->file->setFilename('test.doc');

        $attachmentId = 1;

        $repo = $this->createMock(EntityRepository::class);
        $this->doctrine->expects(self::once())
            ->method('getRepository')
            ->willReturn($repo);
        $repo->expects(self::once())
            ->method('find')
            ->with($attachmentId)
            ->willReturn($this->file);

        $environment = $this->createTwigEnvironmentForImagesTemplate();

        $this->attachmentManager->expects(self::once())
            ->method('getResizedImageUrl')
            ->with($this->file, 16, 16);

        $this->attachmentManager->expects(self::once())
            ->method('getFileUrl');

        self::callTwigFunction($this->extension, 'oro_image_view', [$environment, $attachmentId]);
    }

    public function testGetImageViewConfigured(): void
    {
        $this->file->setFilename('test.doc');
        $this->file->setParentEntityClass($parentEntityClass = \stdClass::class);
        $this->file->setParentEntityFieldName($fieldName = 'sampleField');

        $config = $this->createMock(Config::class);
        $size = 120;

        $environment = $this->createTwigEnvironmentForImagesTemplate();

        $this->configManager->expects(self::once())
            ->method('getFieldConfig')
            ->with('attachment', $parentEntityClass, $fieldName)
            ->willReturn($config);

        $config->expects(self::exactly(2))
            ->method('get')
            ->willReturn($size);

        $this->attachmentManager->expects(self::once())
            ->method('getResizedImageUrl')
            ->with($this->file, $size, $size);

        $this->attachmentManager->expects(self::once())
            ->method('getFileUrl');

        self::callTwigFunction(
            $this->extension,
            'oro_image_view',
            [$environment, $this->file]
        );
    }

    /**
     * @return mixed|\PHPUnit\Framework\MockObject\MockObject|Environment
     */
    private function createTwigEnvironmentForImagesTemplate(): mixed
    {
        $environment = $this->createMock(Environment::class);
        $environment->expects(self::once())
            ->method('render')
            ->with(
                '@OroAttachment/Twig/image.html.twig',
                [
                    'url' => '',
                    'fileName' => null,
                    'title' => '',
                    'imagePath' => ''
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
}
