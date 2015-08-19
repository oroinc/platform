<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Formatter;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Formatter\ImageLinkFormatter;

class ImageLinkFormatterTest extends \PHPUnit_Framework_TestCase
{
    /** @var ImageLinkFormatter */
    protected $formatter;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $translator;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $manager;

    protected function setUp()
    {
        $this->manager = $this
            ->getMockBuilder('Oro\Bundle\AttachmentBundle\Manager\AttachmentManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->translator = $this->getMockBuilder('Symfony\Component\Translation\TranslatorInterface')
            ->getMock();
        $this->formatter  = new ImageLinkFormatter($this->manager, $this->translator);
    }

    public function testGetFormatterName()
    {
        $this->assertEquals('image_link', $this->formatter->getFormatterName());
    }

    public function testGetSupportedTypes()
    {
        $this->assertEquals(['image'], $this->formatter->getSupportedTypes());
    }

    public function testGetDefaultValue()
    {
        $this->translator
            ->expects($this->once())
            ->method('trans')
            ->with('oro.attachment.formatter.image_link.default')
            ->willReturn('test');
        $this->assertEquals('test', $this->formatter->getDefaultValue());
    }

    public function testFormat()
    {
        $file = new File();
        $file->setOriginalFilename('test.png');

        $this->manager
            ->expects($this->once())
            ->method('getResizedImageUrl')
            ->with($file, 100, 100)
            ->willReturn('http://test.com/image.png');
        $this->assertEquals('<a href="http://test.com/image.png">test.png</a>', $this->formatter->format($file));
    }

    public function testFormatWithArguments()
    {
        $file = new File();
        $file->setOriginalFilename('some_name.png');

        $width = 20;
        $height = 30;
        $title = 'test title';

        $this->manager
            ->expects($this->once())
            ->method('getResizedImageUrl')
            ->with($file, $width, $height)
            ->willReturn('http://test.com/image.png');
        $this->assertEquals(
            '<a href="http://test.com/image.png">test title</a>',
            $this->formatter->format(
                $file,
                [
                    'width' => $width,
                    'height' => $height,
                    'title' => $title
                ]
            )
        );
    }
}
