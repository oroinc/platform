<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Twig;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\AttachmentBundle\Tests\Unit\Fixtures\TestAttachment;
use Oro\Bundle\AttachmentBundle\Tests\Unit\Fixtures\TestClass;
use Oro\Bundle\AttachmentBundle\Tests\Unit\Fixtures\TestTemplate;
use Oro\Bundle\AttachmentBundle\Twig\FileExtension;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use Twig\Environment;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class FileExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var FileExtension */
    private $extension;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $manager;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $attachmentConfigProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var TestAttachment */
    private $attachment;

    public function setUp()
    {
        $this->manager = $this->createMock(AttachmentManager::class);
        $this->attachmentConfigProvider = $this->createMock(ConfigProvider::class);
        $configManager = $this->createMock(ConfigManager::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);

        $configManager->expects($this->any())
            ->method('getProvider')
            ->with('attachment')
            ->willReturn($this->attachmentConfigProvider);

        $container = self::getContainerBuilder()
            ->add('oro_attachment.manager', $this->manager)
            ->add('oro_entity_config.config_manager', $configManager)
            ->add('doctrine', $this->doctrine)
            ->getContainer($this);

        $this->extension = new FileExtension($container);

        $this->attachment = new TestAttachment();
    }

    public function testGetName()
    {
        $this->assertEquals('oro_attachment_file', $this->extension->getName());
    }

    public function testGetFileUrl()
    {
        $parentEntity = new TestClass();
        $parentField = 'test_field';
        $this->manager->expects($this->once())
            ->method('getFileUrl')
            ->with($parentEntity, $parentField, $this->attachment, 'download', true);

        self::callTwigFunction(
            $this->extension,
            'file_url',
            [$parentEntity, $parentField, $this->attachment, 'download', true]
        );
    }

    public function testGetResizedImageUrl()
    {
        $this->manager->expects($this->once())
            ->method('getResizedImageUrl')
            ->with($this->attachment, 110, 120);

        self::callTwigFunction($this->extension, 'resized_image_url', [$this->attachment, 110, 120]);
    }

    public function testGetAttachmentIcon()
    {
        $this->manager->expects($this->once())
            ->method('getAttachmentIconClass')
            ->with($this->attachment);

        self::callTwigFunction($this->extension, 'oro_attachment_icon', [$this->attachment]);
    }

    public function testGetEmptyFileView()
    {
        $parentEntity = new TestClass();
        $environment = $this->createMock(Environment::class);

        $this->assertEquals(
            '',
            self::callTwigFunction(
                $this->extension,
                'oro_file_view',
                [$environment, $parentEntity, $this->attachment]
            )
        );
    }

    public function testGetFileView()
    {
        $parentEntity = new TestClass();
        $parentField = 'test_field';
        $this->attachment->setFilename('test.doc');
        $environment = $this->createMock(Environment::class);

        $template = new TestTemplate(new Environment($this->getLoader()));
        $environment->expects($this->once())
            ->method('loadTemplate')
            ->willReturn($template);

        $this->manager->expects($this->once())
            ->method('getAttachmentIconClass')
            ->with($this->attachment);

        $this->manager->expects($this->once())
            ->method('getFileUrl');

        self::callTwigFunction(
            $this->extension,
            'oro_file_view',
            [$environment, $parentEntity, $parentField, $this->attachment]
        );
    }

    public function testGetEmptyImageView()
    {
        $environment = $this->getMockBuilder(Environment::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->assertEquals(
            '',
            self::callTwigFunction($this->extension, 'oro_image_view', [$environment, $this->attachment])
        );
    }

    public function testGetImageView()
    {
        $parentEntity = new TestClass();
        $this->attachment->setFilename('test.doc');
        $environment = $this->createMock(Environment::class);

        $template = new TestTemplate(new Environment($this->getLoader()));
        $environment->expects($this->once())
            ->method('loadTemplate')
            ->willReturn($template);

        $this->manager->expects($this->once())
            ->method('getResizedImageUrl')
            ->with($this->attachment, 16, 16);

        $this->manager->expects($this->once())
            ->method('getFileUrl');

        self::callTwigFunction($this->extension, 'oro_image_view', [$environment, $parentEntity, $this->attachment]);
    }

    public function testGetImageViewConfigured()
    {
        $parentEntity = new TestClass();
        $this->attachment->setFilename('test.doc');
        $environment = $this->createMock(Environment::class);

        $template = new TestTemplate(new Environment($this->getLoader()));
        $environment->expects($this->once())
            ->method('loadTemplate')
            ->willReturn($template);

        $config = $this->createMock('Oro\Bundle\EntityConfigBundle\Config\Config');

        $this->attachmentConfigProvider->expects($this->once())
            ->method('getConfig')
            ->willReturn($config);

        $config->expects($this->exactly(2))
            ->method('get')
            ->willReturn(120);

        $this->manager->expects($this->once())
            ->method('getResizedImageUrl')
            ->with($this->attachment, 120, 120);

        $this->manager->expects($this->once())
            ->method('getFileUrl');

        self::callTwigFunction(
            $this->extension,
            'oro_image_view',
            [$environment, $parentEntity, $this->attachment, new TestClass(), 'testField']
        );
    }

    public function testGetFilteredImageUrl()
    {
        $this->manager->expects($this->once())
            ->method('getFilteredImageUrl')
            ->with($this->attachment, 'testFilter');

        self::callTwigFunction($this->extension, 'filtered_image_url', [$this->attachment, 'testFilter']);
    }

    public function testGetTypeIsImage()
    {
        $this->manager->expects($this->once())
            ->method('isImageType')
            ->with('image/jpeg');

        self::callTwigFunction($this->extension, 'oro_type_is_image', ['image/jpeg']);
    }

    public function testIsPreviewAvailable()
    {
        $this->manager->expects($this->once())
            ->method('isImageType')
            ->with('image/jpeg');

        self::callTwigFunction($this->extension, 'oro_is_preview_available', ['image/jpeg']);
    }

    public function testGetFileIconsConfig()
    {
        $this->manager->expects($this->once())
            ->method('getFileIcons');

        self::callTwigFunction($this->extension, 'oro_file_icons_config', []);
    }

    public function testGetConfiguredImageUrl()
    {
        $parent = new TestAttachment();
        $config = $this->createMock('Oro\Bundle\EntityConfigBundle\Config\Config');

        $this->attachmentConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with('Oro\Bundle\AttachmentBundle\Tests\Unit\Fixtures\TestAttachment', 'testField')
            ->willReturn($config);

        $config->expects($this->exactly(2))
            ->method('get')
            ->willReturn(45);

        $this->attachment->setFilename('test.doc');
        $this->manager->expects($this->once())
            ->method('getResizedImageUrl')
            ->with($this->attachment, 45, 45);

        self::callTwigFunction(
            $this->extension,
            'oro_configured_image_url',
            [$parent, 'testField', $this->attachment]
        );
    }

    public function testGetImageViewWIthIntegerAttachmentParameter()
    {
        $parentEntity = new TestClass();
        $this->attachment->setFilename('test.doc');
        $attachmentId = 1;
        $repo = $this->createMock('Doctrine\Common\Persistence\ObjectRepository');

        $this->doctrine->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        $repo->expects($this->once())
            ->method('find')
            ->with($attachmentId)
            ->willReturn($this->attachment);

        $environment = $this->createMock(Environment::class);

        $template = new TestTemplate(new Environment($this->getLoader()));
        $environment->expects($this->once())
            ->method('loadTemplate')
            ->willReturn($template);

        $this->manager->expects($this->once())
            ->method('getResizedImageUrl')
            ->with($this->attachment, 16, 16);

        $this->manager->expects($this->once())
            ->method('getFileUrl');

        self::callTwigFunction(
            $this->extension,
            'oro_image_view',
            [$environment, $parentEntity, $attachmentId]
        );
    }
}
