<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Formatter;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Formatter\ImageEncodedFormatter;

class ImageEncodedFormatterTest extends \PHPUnit\Framework\TestCase
{
    /** @var ImageEncodedFormatter */
    protected $formatter;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $fileLocator;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $fileManager;

    protected function setUp()
    {
        $this->fileManager = $this
            ->getMockBuilder('Oro\Bundle\AttachmentBundle\Manager\FileManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->fileLocator = $this
            ->getMockBuilder('Symfony\Component\HttpKernel\Config\FileLocator')
            ->disableOriginalConstructor()
            ->getMock();
        $this->formatter   = new ImageEncodedFormatter($this->fileManager, $this->fileLocator);
    }

    public function testGetFormatterName()
    {
        $this->assertEquals('image_encoded', $this->formatter->getFormatterName());
    }

    public function testFormat()
    {
        $file = new File();
        $file->setMimeType('image/png');
        $file->setOriginalFilename('test.png');
        $expected = '<img src="data:image/png;base64,dGVzdA==" alt = "test.png"/>';

        $this->fileManager
            ->expects($this->once())
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

        $this->fileLocator
            ->expects($this->once())
            ->method('locate')
            ->willReturn(__DIR__ . '/../Fixtures/testFile/test.png');

        $this->assertEquals($expected, $this->formatter->getDefaultValue());
    }

    public function testGetSupportedTypes()
    {
        $this->assertEquals(['image'], $this->formatter->getSupportedTypes());
    }

    public function testIsDefaultFormatter()
    {
        $this->assertTrue($this->formatter->isDefaultFormatter());
    }
}
