<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Twig;


use Oro\Bundle\AttachmentBundle\Tests\Unit\Fixtures\TestClass;
use Oro\Bundle\AttachmentBundle\Tests\Unit\Fixtures\TestTemplate;
use Oro\Bundle\AttachmentBundle\Tests\Unit\Fixtures\TestAttachment;
use Oro\Bundle\AttachmentBundle\Twig\AttachmentExtension;

class AttachmentExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AttachmentExtension
     */
    protected $extension;

    /** @var \PHPUnit_Framework_MockObject_MockObject  */
    protected $manager;

    /** @var \PHPUnit_Framework_MockObject_MockObject  */
    protected $attachmentConfigProvider;

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
        $configManager->expects($this->once())
            ->method('getProvider')
            ->with('attachment')
            ->will($this->returnValue($this->attachmentConfigProvider));

        $this->extension = new AttachmentExtension($this->manager, $configManager);
        $this->attachment = new TestAttachment();
    }

    public function testGetFunctions()
    {
        $result = $this->extension->getFunctions();
        $functions = [
            'oro_attachment_url',
            'oro_resized_attachment_url',
            'oro_attachment_icon',
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
        $this->assertEquals('oro_attachment', $this->extension->getName());
    }

    public function testGetAttachmentUrl()
    {
        $parentEntity = new TestClass();
        $parentField = 'test_field';
        $this->manager->expects($this->once())
            ->method('getAttachmentUrl')
            ->with($parentEntity, $parentField, $this->attachment, 'download', true);

        $this->extension->getAttachmentUrl($parentEntity, $parentField, $this->attachment, 'download', true);
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
            ->method('getAttachmentUrl');
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
            ->with($this->attachment, 32, 32);
        $this->manager->expects($this->once())
            ->method('getAttachmentUrl');

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
            ->method('getAttachmentUrl');

        $this->extension->getImageView($environment, $parentEntity, $this->attachment, new TestClass(), 'testField');
    }
}
