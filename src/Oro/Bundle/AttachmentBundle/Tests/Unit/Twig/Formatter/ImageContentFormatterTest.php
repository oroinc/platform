<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Twig\Formatter;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Twig\Formatter\ImageContentFormatter;

class ImageContentFormatterTest extends \PHPUnit_Framework_TestCase
{
    /** @var ImageContentFormatter */
    protected $formatter;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $fileLocator;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $manager;

    protected function setUp()
    {
        $this->manager = $this
            ->getMockBuilder('Oro\Bundle\AttachmentBundle\Manager\AttachmentManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->fileLocator = $this
            ->getMockBuilder('Symfony\Component\HttpKernel\Config\FileLocator')
            ->disableOriginalConstructor()
            ->getMock();
        $this->formatter   = new ImageContentFormatter($this->manager, $this->fileLocator);
    }

    public function testGetFormatterName()
    {
        $this->assertEquals('inline_content', $this->formatter->getFormatterName());
    }

    public function testFormat()
    {
        $file = new File();
        $file->setMimeType('image/png');
        $expected = 'data:image/png;base64,' . base64_encode('test');

        $this->manager
            ->expects($this->once())
            ->method('getContent')
            ->with($file)
            ->willReturn('test');

        $this->assertEquals($expected, $this->formatter->format($file));
    }

    public function testGetDefaultValue()
    {

        $expected = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAIAAAACAQMAAABIeJ9nAAAAA1BMVEX///+'
            . 'nxBvIAAAAAWJLR0QAiAUdSAAAAAlwSFlzAAALEwAACxMBAJqcGAAAAAd0SU1FB98GEAgrLyNXN+0AAAAmaVRYdENvbW1lbnQAAAAAA'
            . 'ENyZWF0ZWQgd2l0aCBHSU1QIG9uIGEgTWFjleRfWwAAAAxJREFUCNdjYGBgAAAABAABJzQnCgAAAABJRU5ErkJggg==';

        $this->fileLocator
            ->expects($this->once())
            ->method('locate')
            ->willReturn(__DIR__ . '/../../Fixtures/testFile/test.png');

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
