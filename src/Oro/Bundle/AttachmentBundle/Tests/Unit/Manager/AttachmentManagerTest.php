<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Manager;

use Gaufrette\Stream\InMemoryBuffer;
use Gaufrette\StreamMode;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\AttachmentBundle\Tests\Unit\Fixtures\TestAttachment;

class AttachmentManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var AttachmentManager  */
    protected $attachmentManager;

    /** @var  \PHPUnit_Framework_MockObject_MockObject */
    protected $filesystem;

    /** @var  \PHPUnit_Framework_MockObject_MockObject */
    protected $router;

    /**
     * @var TestAttachment
     */
    protected $attachment;

    public function setUp()
    {
        $filesystemMap = $this->getMockBuilder('Knp\Bundle\GaufretteBundle\FilesystemMap')
            ->disableOriginalConstructor()
            ->getMock();

        $this->filesystem = $this->getMockBuilder('Gaufrette\Filesystem')
            ->disableOriginalConstructor()
            ->getMock();

        $filesystemMap->expects($this->once())
            ->method('get')
            ->with('attachments')
            ->will($this->returnValue($this->filesystem));

        $this->router = $this->getMockBuilder('Symfony\Bundle\FrameworkBundle\Routing\Router')
            ->disableOriginalConstructor()
            ->getMock();

        $ileIcons = [
            'default' => 'icon_default',
            'txt' => 'icon_txt'
        ];

        $this->attachment = new TestAttachment();
        $this->attachment->setFilename('testFile.txt');
        $this->attachment->setOriginalFilename('testFile.txt');

        $this->attachmentManager = new AttachmentManager($filesystemMap, $this->router, $ileIcons);
    }

    public function testGetContent()
    {
        $fileContent = 'test data';

        $file = $this->getMockBuilder('Gaufrette\File')
            ->disableOriginalConstructor()
            ->getMock();

        $this->filesystem->expects($this->once())
            ->method('get')
            ->with('testFile.txt')
            ->will($this->returnValue($file));

        $file->expects($this->once())
            ->method('getContent')
            ->will($this->returnValue($fileContent));

        $this->assertEquals($fileContent, $this->attachmentManager->getContent($this->attachment));
    }

    public function testGetAttachmentUrl()
    {
        $this->attachment->setId(1);
        $this->router->expects($this->once())
            ->method('generate')
            ->with(
                'oro_attachment_file',
                [
                    'type' => 'download',
                    'id' => 1,
                    'filename' => 'testFile.txt'
                ],
                true
            );
        $this->attachmentManager->getAttachmentUrl($this->attachment, 'download', true);
    }

    public function testGetResizedImageUrl()
    {
        $this->attachment->setId(1);
        $this->router->expects($this->once())
            ->method('generate')
            ->with(
                'oro_resize_attachment',
                [
                    'width' => 100,
                    'height' => 50,
                    'id' => 1,
                    'filename' => 'testFile.txt'
                ]
            );
        $this->attachmentManager->getResizedImageUrl($this->attachment, 100, 50);
    }

    public function testGetAttachmentIconClass()
    {
        $this->attachment->setExtension('txt');
        $this->assertEquals('icon_txt', $this->attachmentManager->getAttachmentIconClass($this->attachment));
        $this->attachment->setExtension('doc');
        $this->assertEquals('icon_default', $this->attachmentManager->getAttachmentIconClass($this->attachment));
    }

    public function testUpload()
    {
        $this->attachment->setEmptyFile(false);

        $file = $this->getMockBuilder('Symfony\Component\HttpFoundation\File\File')
            ->disableOriginalConstructor()
            ->getMock();
        $this->attachment->setFile($file);
        $path = __DIR__ . '/../Fixtures/testFile/test.txt';
        $file->expects($this->once())
            ->method('getPathname')
            ->will($this->returnValue(realpath($path)));

        $file->expects($this->once())
            ->method('isFile')
            ->will($this->returnValue(true));

        $memoryBuffer = new InMemoryBuffer($this->filesystem, 'test.txt');

        $this->filesystem->expects($this->once())
            ->method('createStream')
            ->with($this->attachment->getFilename())
            ->will($this->returnValue($memoryBuffer));

        $this->attachmentManager->upload($this->attachment);
        $memoryBuffer->open(new StreamMode('rb+'));
        $memoryBuffer->seek(0);

        $this->assertEquals('Test data', $memoryBuffer->read(100));
    }

    public function testPreUploadDeleteFile()
    {
        $this->attachment->setEmptyFile(true)
            ->setFilename('test.doc')
            ->setExtension('doc')
            ->setOriginalFilename('test.doc');
        $this->filesystem->expects($this->once())
            ->method('has')
            ->with($this->attachment->getFilename())
            ->will($this->returnValue(true));
        $this->filesystem->expects($this->once())
            ->method('delete')
            ->with($this->attachment->getFilename());
        $this->attachmentManager->preUpload($this->attachment);
        $this->assertNull($this->attachment->getFilename());
        $this->assertNull($this->attachment->getExtension());
        $this->assertNull($this->attachment->getOriginalFilename());
    }

    public function testPreUpload()
    {
        $this->attachment->setEmptyFile(false)
            ->setFilename('test.doc');
        $file = $this->getMockBuilder('Symfony\Component\HttpFoundation\File\File')
            ->disableOriginalConstructor()
            ->getMock();
        $this->attachment->setFile($file);
        $file->expects($this->once())
            ->method('isFile')
            ->will($this->returnValue(true));
        $this->filesystem->expects($this->once())
            ->method('delete')
            ->with($this->attachment->getFilename());
        $this->filesystem->expects($this->once())
            ->method('has')
            ->with($this->attachment->getFilename())
            ->will($this->returnValue(true));
        $file->expects($this->once())
            ->method('guessExtension')
            ->will($this->returnValue('xls'));
        $file->expects($this->once())
            ->method('getFileName')
            ->will($this->returnValue('newFile.xls'));
        $file->expects($this->once())
            ->method('getMimeType')
            ->will($this->returnValue('text/css'));
        $file->expects($this->once())
            ->method('getSize')
            ->will($this->returnValue(1024));
        $adapter = $file = $this->getMockBuilder('Gaufrette\Adapter\Cache')
            ->disableOriginalConstructor()
            ->getMock();
        $this->filesystem->expects($this->any())
            ->method('getAdapter')
            ->will($this->returnValue($adapter));
        $adapter->expects($this->once())
            ->method('setMetadata');

        $this->attachmentManager->preUpload($this->attachment);

        $this->assertEquals('newFile.xls', $this->attachment->getOriginalFilename());
        $this->assertEquals('xls', $this->attachment->getExtension());
        $this->assertEquals('text/css', $this->attachment->getMimeType());
        $this->assertEquals(1024, $this->attachment->getFileSize());
    }
}
