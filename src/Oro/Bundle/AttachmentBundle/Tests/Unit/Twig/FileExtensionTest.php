<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Twig;

use Oro\Bundle\AttachmentBundle\Tests\Unit\Fixtures\TestClass;
use Oro\Bundle\AttachmentBundle\Tests\Unit\Fixtures\TestTemplate;
use Oro\Bundle\AttachmentBundle\Tests\Unit\Fixtures\TestAttachment;
use Oro\Bundle\AttachmentBundle\Twig\FileExtension;

class FileExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var FileExtension
     */
    protected $extension;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $manager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $attachmentConfigProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $doctrine;

    /** @var TestAttachment */
    protected $attachment;

    public function setUp()
    {
        $this->manager = $this->getMockBuilder('Oro\Bundle\AttachmentBundle\Manager\AttachmentManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->attachmentConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrine = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();
        $configManager->expects($this->once())
            ->method('getProvider')
            ->with('attachment')
            ->will($this->returnValue($this->attachmentConfigProvider));

        $this->extension = new FileExtension($this->manager, $configManager, $this->doctrine);
        $this->attachment = new TestAttachment();
    }

    public function testGetFunctions()
    {
        $result = $this->extension->getFunctions();
        $functions = [
            'file_url',
            'file_size',
            'resized_image_url',
            'filtered_image_url',
            'oro_configured_image_url',
            'oro_attachment_icon',
            'oro_type_is_image',
            'oro_is_preview_available',
            'oro_file_icons_config',
            'oro_file_view',
            'oro_image_view'
        ];

        /** @var $function \Twig_SimpleFunction */
        foreach ($result as $function) {
            $this->assertTrue(in_array($function->getName(), $functions));
        }
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

        $this->extension->getFIleUrl($parentEntity, $parentField, $this->attachment, 'download', true);
    }

    public function testGetResizedImageUrl()
    {
        $this->manager->expects($this->once())
            ->method('getResizedImageUrl')
            ->with($this->attachment, 110, 120);

        $this->extension->getResizedImageUrl($this->attachment, 110, 120);
    }

    public function testGetAttachmentIcon()
    {
        $this->manager->expects($this->once())
            ->method('getAttachmentIconClass')
            ->with($this->attachment);

        $this->extension->getAttachmentIcon($this->attachment);
    }

    public function testGetEmptyFileView()
    {
        $parentEntity = new TestClass();
        $environment = $this->getMockBuilder('\Twig_Environment')
            ->disableOriginalConstructor()
            ->getMock();
        $this->assertEquals('', $this->extension->getFileView($environment, $parentEntity, $this->attachment));
    }

    public function testGetFileView()
    {
        $parentEntity = new TestClass();
        $parentField = 'test_field';
        $this->attachment->setFilename('test.doc');
        $environment = $this->getMockBuilder('\Twig_Environment')
            ->disableOriginalConstructor()
            ->getMock();
        $template = new TestTemplate(new \Twig_Environment());
        $environment->expects($this->once())
            ->method('loadTemplate')
            ->will($this->returnValue($template));
        $this->manager->expects($this->once())
            ->method('getAttachmentIconClass')
            ->with($this->attachment);
        $this->manager->expects($this->once())
            ->method('getFileUrl');
        $this->extension->getFileView($environment, $parentEntity, $parentField, $this->attachment);
    }

    public function testGetEmptyImageView()
    {
        $environment = $this->getMockBuilder('\Twig_Environment')
            ->disableOriginalConstructor()
            ->getMock();
        $this->assertEquals('', $this->extension->getImageView($environment, $this->attachment));
    }

    public function testGetImageView()
    {
        $parentEntity = new TestClass();
        $this->attachment->setFilename('test.doc');
        $environment = $this->getMockBuilder('\Twig_Environment')
            ->disableOriginalConstructor()
            ->getMock();
        $template = new TestTemplate(new \Twig_Environment());
        $environment->expects($this->once())
            ->method('loadTemplate')
            ->will($this->returnValue($template));
        $this->manager->expects($this->once())
            ->method('getResizedImageUrl')
            ->with($this->attachment, 16, 16);
        $this->manager->expects($this->once())
            ->method('getFileUrl');

        $this->extension->getImageView($environment, $parentEntity, $this->attachment);
    }

    public function testGetImageViewConfigured()
    {
        $parentEntity = new TestClass();
        $this->attachment->setFilename('test.doc');
        $environment = $this->getMockBuilder('\Twig_Environment')
            ->disableOriginalConstructor()
            ->getMock();
        $template = new TestTemplate(new \Twig_Environment());
        $environment->expects($this->once())
            ->method('loadTemplate')
            ->will($this->returnValue($template));
        $config = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\Config')
            ->disableOriginalConstructor()
            ->getMock();
        $this->attachmentConfigProvider->expects($this->once())
            ->method('getConfig')
            ->will($this->returnValue($config));
        $config->expects($this->exactly(2))
            ->method('get')
            ->will($this->returnValue(120));
        $this->manager->expects($this->once())
            ->method('getResizedImageUrl')
            ->with($this->attachment, 120, 120);
        $this->manager->expects($this->once())
            ->method('getFileUrl');

        $this->extension->getImageView($environment, $parentEntity, $this->attachment, new TestClass(), 'testField');
    }

    public function testGetFilteredImageUrl()
    {
        $this->manager->expects($this->once())
            ->method('getFilteredImageUrl')
            ->with($this->attachment, 'testFilter');

        $this->extension->getFilteredImageUrl($this->attachment, 'testFilter');
    }

    public function testGetTypeIsImage()
    {
        $this->manager->expects($this->once())
            ->method('isImageType')
            ->with('image/jpeg');

        $this->extension->getTypeIsImage('image/jpeg');
    }

    public function testIsPreviewAvailable()
    {
        $this->manager->expects($this->once())
            ->method('isImageType')
            ->with('image/jpeg');

        $this->extension->isPreviewAvailable('image/jpeg');
    }

    public function testGetFileIconsConfig()
    {
        $this->manager->expects($this->once())
            ->method('getFileIcons');

        $this->extension->getFileIconsConfig();
    }

    public function testGetConfiguredImageUrl()
    {
        $parent = new TestAttachment();
        $config = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\Config')
            ->disableOriginalConstructor()
            ->getMock();
        $this->attachmentConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with('Oro\Bundle\AttachmentBundle\Tests\Unit\Fixtures\TestAttachment', 'testField')
            ->will($this->returnValue($config));
        $config->expects($this->exactly(2))
            ->method('get')
            ->will($this->returnValue(45));
        $this->attachment->setFilename('test.doc');
        $this->manager->expects($this->once())
            ->method('getResizedImageUrl')
            ->with($this->attachment, 45, 45);

        $this->extension->getConfiguredImageUrl($parent, 'testField', $this->attachment);
    }

    public function testGetImageViewWIthIntegerAttachmentParameter()
    {
        $parentEntity = new TestClass();
        $this->attachment->setFilename('test.doc');
        $attachmentId = 1;
        $repo = $this->getMockBuilder('Doctrine\Common\Persistence\ObjectRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrine->expects($this->once())
            ->method('getRepository')
            ->will($this->returnValue($repo));
        $repo->expects($this->once())
            ->method('find')
            ->with($attachmentId)
            ->will($this->returnValue($this->attachment));
        $environment = $this->getMockBuilder('\Twig_Environment')
            ->disableOriginalConstructor()
            ->getMock();
        $template = new TestTemplate(new \Twig_Environment());
        $environment->expects($this->once())
            ->method('loadTemplate')
            ->will($this->returnValue($template));
        $this->manager->expects($this->once())
            ->method('getResizedImageUrl')
            ->with($this->attachment, 16, 16);
        $this->manager->expects($this->once())
            ->method('getFileUrl');

        $this->extension->getImageView($environment, $parentEntity, $attachmentId);
    }
}
