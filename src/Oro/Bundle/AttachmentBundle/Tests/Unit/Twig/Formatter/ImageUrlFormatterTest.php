<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Twig\Formatter;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Twig\Formatter\ImageUrlFormatter;

class ImageUrlFormatterTest extends \PHPUnit_Framework_TestCase
{
    /** @var ImageUrlFormatter */
    protected $formatter;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $manager;

    protected function setUp()
    {
        $this->manager = $this
            ->getMockBuilder('Oro\Bundle\AttachmentBundle\Manager\AttachmentManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->formatter = new ImageUrlFormatter($this->manager);
    }

    public function testGetFormatterName()
    {
        $this->assertEquals('image_url', $this->formatter->getFormatterName());
    }

    public function testFormat()
    {
        $file = new File();

        $this->manager
            ->expects($this->once())
            ->method('getResizedImageUrl')
            ->with($file, ImageUrlFormatter::DEFAULT_WIDTH, ImageUrlFormatter::DEFAULT_HEIGHT);
        $this->formatter->format($file);
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
