<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Formatter;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Formatter\ImageSrcFormatter;

class ImageSrcFormatterTest extends \PHPUnit\Framework\TestCase
{
    /** @var ImageSrcFormatter */
    protected $formatter;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $manager;

    protected function setUp(): void
    {
        $this->manager = $this
            ->getMockBuilder('Oro\Bundle\AttachmentBundle\Manager\AttachmentManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->formatter = new ImageSrcFormatter($this->manager);
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

    public function testGetDefaultValue()
    {
        $this->assertEquals('#', $this->formatter->getDefaultValue());
    }
}
