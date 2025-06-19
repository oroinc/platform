<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Formatter;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Formatter\ImageLinkFormatter;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class ImageLinkFormatterTest extends TestCase
{
    private AttachmentManager&MockObject $manager;
    private TranslatorInterface&MockObject $translator;
    private ImageLinkFormatter $formatter;

    #[\Override]
    protected function setUp(): void
    {
        $this->manager = $this->createMock(AttachmentManager::class);
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->formatter = new ImageLinkFormatter($this->manager, $this->translator);
    }

    public function testGetDefaultValue(): void
    {
        $this->translator->expects($this->once())
            ->method('trans')
            ->with('oro.attachment.formatter.image_link.default')
            ->willReturn('test');
        $this->assertEquals('test', $this->formatter->getDefaultValue());
    }

    public function testFormat(): void
    {
        $file = new File();
        $file->setOriginalFilename('test.png');

        $this->manager->expects($this->once())
            ->method('getResizedImageUrl')
            ->with($file, 100, 100, '')
            ->willReturn('http://test.com/image.png');
        $this->assertEquals('<a href="http://test.com/image.png">test.png</a>', $this->formatter->format($file));
    }

    public function testFormatWithArguments(): void
    {
        $file = new File();
        $file->setOriginalFilename('some_name.png');

        $width = 20;
        $height = 30;
        $title = 'test title';
        $format = 'sample-format';

        $this->manager->expects($this->once())
            ->method('getResizedImageUrl')
            ->with($file, $width, $height, $format)
            ->willReturn('http://test.com/image.png');
        $this->assertEquals(
            '<a href="http://test.com/image.png">test title</a>',
            $this->formatter->format(
                $file,
                [
                    'width' => $width,
                    'height' => $height,
                    'title' => $title,
                    'format' => $format,
                ]
            )
        );
    }
}
