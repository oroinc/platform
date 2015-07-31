<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Formatter;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Formatter\ImageSrcFormatter;

class ImageSrcFormatterTest extends \PHPUnit_Framework_TestCase
{
    /** @var ImageSrcFormatter */
    protected $formatter;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $manager;

    protected function setUp()
    {
        $this->manager = $this
            ->getMockBuilder('Oro\Bundle\AttachmentBundle\Manager\AttachmentManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->formatter = new ImageSrcFormatter($this->manager);
    }

    public function testGetFormatterName()
    {
        $this->assertEquals('image_src', $this->formatter->getFormatterName());
    }

    public function testFormat()
    {
        $file = new File();

        $this->manager
            ->expects($this->once())
            ->method('getResizedImageUrl')
            ->with($file, 100, 100)
            ->willReturn('http://test.com/image.png');
        $this->assertEquals('http://test.com/image.png', $this->formatter->format($file));
    }

    public function testGetSupportedTypes()
    {
        $this->assertEquals(['image'], $this->formatter->getSupportedTypes());
    }

    public function testIsDefaultFormatter()
    {
        $this->assertFalse($this->formatter->isDefaultFormatter());
    }

    public function testGetDefaultValue()
    {
        $this->assertEquals('#', $this->formatter->getDefaultValue());
    }
}
