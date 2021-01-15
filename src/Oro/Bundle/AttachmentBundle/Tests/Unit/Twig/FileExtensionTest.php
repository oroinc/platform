<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Twig;

use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\AttachmentBundle\Provider\FileTitleProviderInterface;
use Oro\Bundle\AttachmentBundle\Provider\FileUrlProviderInterface;
use Oro\Bundle\AttachmentBundle\Tests\Unit\Fixtures\TestFile;
use Oro\Bundle\AttachmentBundle\Tests\Unit\Fixtures\TestTemplate;
use Oro\Bundle\AttachmentBundle\Tests\Unit\Stub\ParentEntity;
use Oro\Bundle\AttachmentBundle\Twig\FileExtension;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
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

    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $attachmentConfigProvider;

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
        $configManager = $this->createMock(ConfigManager::class);
        $configManager
            ->method('getProvider')
            ->with('attachment')
            ->willReturn($this->attachmentConfigProvider = $this->createMock(ConfigProvider::class));
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->propertyAccessor = $this->createMock(PropertyAccessorInterface::class);
        $this->fileTitleProvider = $this->createMock(FileTitleProviderInterface::class);

        $serviceLocator = self::getContainerBuilder()
            ->add(AttachmentManager::class, $this->attachmentManager)
            ->add(ConfigManager::class, $configManager)
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
        $this->attachmentManager
            ->expects(self::once())
            ->method('getFileUrl')
            ->with(
                $this->file,
                $action = FileUrlProviderInterface::FILE_ACTION_DOWNLOAD,
                $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH
            );

        self::callTwigFunction(
            $this->extension,
            'file_url',
            [$this->file, $action, true]
        );
    }

    public function testGetResizedImageUrl(): void
    {
        $this->attachmentManager
            ->expects(self::once())
            ->method('getResizedImageUrl')
            ->with($this->file, $width = 110, $height = 120);

        self::callTwigFunction($this->extension, 'resized_image_url', [$this->file, $width, $height]);
    }

    public function testGetFilteredImageUrl(): void
    {
        $this->attachmentManager
            ->expects(self::once())
            ->method('getFilteredImageUrl')
            ->with($this->file, $filter = 'testFilter');

        self::callTwigFunction($this->extension, 'filtered_image_url', [$this->file, $filter]);
    }

    public function testGetConfiguredImageUrl(): void
    {
        $this->attachmentConfigProvider
            ->expects(self::once())
            ->method('getConfig')
            ->with(ParentEntity::class, 'testField')
            ->willReturn($config = $this->createMock(Config::class));

        $config
            ->expects(self::exactly(2))
            ->method('get')
            ->willReturn(45);

        $this->file->setFilename('test.doc');

        $this->attachmentManager
            ->expects(self::once())
            ->method('getResizedImageUrl')
            ->with($this->file, 45, 45);

        $this->propertyAccessor
            ->expects(self::never())
            ->method('getValue');

        self::callTwigFunction(
            $this->extension,
            'oro_configured_image_url',
            [new ParentEntity(1, null, []), 'testField', $this->file]
        );
    }

    public function testGetConfiguredImageUrlWhenFileFromParentEntity(): void
    {
        $this->attachmentConfigProvider
            ->expects(self::once())
            ->method('getConfig')
            ->with(ParentEntity::class, 'file')
            ->willReturn($config = $this->createMock(Config::class));

        $config
            ->expects(self::exactly(2))
            ->method('get')
            ->willReturn(45);

        $this->attachmentManager
            ->expects(self::once())
            ->method('getResizedImageUrl')
            ->with($file = new File(), 45, 45);

        $file->setFilename('test.doc');

        $this->propertyAccessor
            ->expects(self::once())
            ->method('getValue')
            ->with($parentEntity = new ParentEntity(1, $file, []), $fieldName = 'file')
            ->willReturn($file);

        self::callTwigFunction(
            $this->extension,
            'oro_configured_image_url',
            [$parentEntity, $fieldName]
        );
    }

    public function testGetConfiguredImageUrlWhenNoFile(): void
    {
        $this->attachmentConfigProvider
            ->expects(self::never())
            ->method('getConfig');

        $this->attachmentManager
            ->expects(self::never())
            ->method('getResizedImageUrl');

        self::callTwigFunction(
            $this->extension,
            'oro_configured_image_url',
            [new ParentEntity(1, null, []), 'file']
        );
    }

    public function testGetAttachmentIcon(): void
    {
        $this->attachmentManager
            ->expects(self::once())
            ->method('getAttachmentIconClass')
            ->with($this->file);

        self::callTwigFunction($this->extension, 'oro_attachment_icon', [$this->file]);
    }

    public function testGetFileView(): void
    {
        $this->file->setFilename('test.doc');

        $environment = $this->createMock(Environment::class);
        $environment->expects(self::once())
            ->method('loadTemplate')
            ->willReturn(new TestTemplate(new Environment($this->getLoader())));

        $this->attachmentManager
            ->expects(self::once())
            ->method('getAttachmentIconClass')
            ->with($this->file);

        $this->attachmentManager
            ->expects(self::once())
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
                [$this->createMock(\Twig\Environment::class), null]
            )
        );
    }

    public function testGetImageView(): void
    {
        $this->file->setFilename('test.doc');

        $environment = $this->createMock(Environment::class);
        $environment->expects(self::once())
            ->method('loadTemplate')
            ->willReturn(new TestTemplate(new Environment($this->getLoader())));

        $this->attachmentManager
            ->expects(self::once())
            ->method('getResizedImageUrl')
            ->with($this->file, 16, 16);

        $this->attachmentManager
            ->expects(self::once())
            ->method('getFileUrl');

        self::callTwigFunction($this->extension, 'oro_image_view', [$environment, $this->file]);
    }

    public function testGetImageViewWithIntegerAttachmentParameter(): void
    {
        $this->file->setFilename('test.doc');

        $this->doctrine
            ->expects(self::once())
            ->method('getRepository')
            ->willReturn($repo = $this->createMock(EntityRepository::class));

        $repo
            ->expects(self::once())
            ->method('find')
            ->with($attachmentId = 1)
            ->willReturn($this->file);

        $environment = $this->createMock(Environment::class);
        $environment->expects(self::once())
            ->method('loadTemplate')
            ->willReturn(new TestTemplate(new Environment($this->getLoader())));

        $this->attachmentManager
            ->expects(self::once())
            ->method('getResizedImageUrl')
            ->with($this->file, 16, 16);

        $this->attachmentManager
            ->expects(self::once())
            ->method('getFileUrl');

        self::callTwigFunction($this->extension, 'oro_image_view', [$environment, $attachmentId]);
    }

    public function testGetImageViewConfigured(): void
    {
        $this->file->setFilename('test.doc');
        $this->file->setParentEntityClass($parentEntityClass = \stdClass::class);
        $this->file->setParentEntityFieldName($fieldName = 'sampleField');

        $environment = $this->createMock(Environment::class);
        $environment->expects(self::once())
            ->method('loadTemplate')
            ->willReturn(new TestTemplate(new Environment($this->getLoader())));

        $this->attachmentConfigProvider
            ->expects(self::once())
            ->method('getConfig')
            ->with($parentEntityClass, $fieldName)
            ->willReturn($config = $this->createMock(Config::class));

        $config
            ->expects(self::exactly(2))
            ->method('get')
            ->willReturn($size = 120);

        $this->attachmentManager
            ->expects(self::once())
            ->method('getResizedImageUrl')
            ->with($this->file, $size, $size);

        $this->attachmentManager
            ->expects(self::once())
            ->method('getFileUrl');

        self::callTwigFunction(
            $this->extension,
            'oro_image_view',
            [$environment, $this->file]
        );
    }

    public function testGetTypeIsImage(): void
    {
        $this->attachmentManager
            ->expects(self::once())
            ->method('isImageType')
            ->with('image/jpeg');

        self::callTwigFunction($this->extension, 'oro_type_is_image', ['image/jpeg']);
    }

    public function testIsPreviewAvailable(): void
    {
        $this->attachmentManager
            ->expects(self::once())
            ->method('isImageType')
            ->with($mimeType = 'image/jpeg');

        self::callTwigFunction($this->extension, 'oro_is_preview_available', [$mimeType]);
    }

    public function testGetFileIconsConfig(): void
    {
        $this->attachmentManager
            ->expects(self::once())
            ->method('getFileIcons');

        self::callTwigFunction($this->extension, 'oro_file_icons_config', []);
    }

    public function testGetTitle(): void
    {
        $this->fileTitleProvider
            ->expects(self::once())
            ->method('getTitle')
            ->with($this->file, $localization = $this->createMock(Localization::class));

        self::callTwigFunction($this->extension, 'oro_file_title', [$this->file, $localization]);
    }

    public function testGetTitleWhenNullFile(): void
    {
        $this->fileTitleProvider
            ->expects(self::never())
            ->method('getTitle');

        self::callTwigFunction($this->extension, 'oro_file_title', [null]);
    }

    public function testGetTitleWhenNoLozalization(): void
    {
        $this->fileTitleProvider
            ->expects(self::once())
            ->method('getTitle')
            ->with($this->file);

        self::callTwigFunction($this->extension, 'oro_file_title', [$this->file]);
    }
}
