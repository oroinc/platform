<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Formatter;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Formatter\ImageEncodedFormatter;
use Oro\Bundle\AttachmentBundle\Manager\FileManager;
use Symfony\Component\HttpKernel\Config\FileLocator;

class ImageEncodedFormatterTest extends \PHPUnit\Framework\TestCase
{
    /** @var FileManager|\PHPUnit\Framework\MockObject\MockObject */
    private $fileManager;

    /** @var FileLocator|\PHPUnit\Framework\MockObject\MockObject */
    private $fileLocator;

    /** @var ImageEncodedFormatter */
    private $formatter;

    protected function setUp(): void
    {
        $this->fileManager = $this->createMock(FileManager::class);
        $this->fileLocator = $this->createMock(FileLocator::class);

        $this->formatter = new ImageEncodedFormatter($this->fileManager, $this->fileLocator);
    }

    public function testFormat()
    {
        $file = new File();
        $file->setMimeType('image/png');
        $file->setOriginalFilename('test.png');
        $expected = '<img src="data:image/png;base64,dGVzdA==" alt = "test.png"/>';

        $this->fileManager->expects($this->once())
            ->method('getContent')
            ->with($file)
            ->willReturn('test');

        $this->assertEquals($expected, $this->formatter->format($file));
    }

    public function testGetDefaultValue()
    {
        $expected = '<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAIAAAACAQMAAABIeJ9nAAAAA1BMVEX///+'
            . 'nxBvIAAAAAWJLR0QAiAUdSAAAAAlwSFlzAAALEwAACxMBAJqcGAAAAAd0SU1FB98GEAgrLyNXN+0AAAAmaVRYdENvbW1lbnQAAAAAA'
            . 'ENyZWF0ZWQgd2l0aCBHSU1QIG9uIGEgTWFjleRfWwAAAAxJREFUCNdjYGBgAAAABAABJzQnCgAAAABJRU5ErkJggg==" />';

        $this->fileLocator->expects($this->once())
            ->method('locate')
            ->willReturn(__DIR__ . '/../Fixtures/testFile/test.png');

        $this->assertEquals($expected, $this->formatter->getDefaultValue());
    }
}
