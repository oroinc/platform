<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Formatter;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Formatter\ImageSrcFormatter;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;

class ImageSrcFormatterTest extends \PHPUnit\Framework\TestCase
{
    /** @var AttachmentManager|\PHPUnit\Framework\MockObject\MockObject */
    private $manager;

    /** @var ImageSrcFormatter */
    private $formatter;

    protected function setUp(): void
    {
        $this->manager = $this->createMock(AttachmentManager::class);

        $this->formatter = new ImageSrcFormatter($this->manager);
    }

    public function testFormat()
    {
        $file = new File();

        $this->manager->expects($this->once())
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
