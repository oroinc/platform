<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Manager;

use Symfony\Component\HttpFoundation\File\File;

use Gaufrette\Stream\InMemoryBuffer;
use Gaufrette\StreamMode;

use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\AttachmentBundle\Tests\Unit\Fixtures\TestAttachment;
use Oro\Bundle\AttachmentBundle\Tests\Unit\Fixtures\TestClass;

class AttachmentManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var AttachmentManager  */
    protected $attachmentManager;

    /** @var  \PHPUnit_Framework_MockObject_MockObject */
    protected $filesystem;

    /** @var  \PHPUnit_Framework_MockObject_MockObject */
    protected $router;

    /** @var  \PHPUnit_Framework_MockObject_MockObject */
    protected $associationManager;

    /** @var TestAttachment */
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

        $serviceLink = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink')
            ->disableOriginalConstructor()
            ->getMock();

        $securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $serviceLink->expects($this->any())->method('getService')
            ->will($this->returnValue($securityFacade));

        $securityFacade->expects($this->any())->method('getLoggedUser')
            ->will($this->returnValue(null));

        $this->associationManager = $this
            ->getMockBuilder('Oro\Bundle\EntityExtendBundle\Entity\Manager\AssociationManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->attachmentManager = new AttachmentManager(
            $filesystemMap,
            $this->router,
            $serviceLink,
            $ileIcons,
            $this->associationManager
        );
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

    public function testGetFileUrl()
    {
        $this->attachment->setId(1);
        $this->attachment->setExtension('txt');
        $this->attachment->setOriginalFilename('testFile.withForwardSlash?.txt');
        $fieldName = 'testField';
        $parentEntity = new TestClass();
        $expectsString = 'T3JvXEJ1bmRsZVxBdHRhY2htZW50QnVuZGxlXFRlc3RzXFVuaXRcRml4dHVyZXNcVGVzdENsYXNzfHRlc3RG'.
            'aWVsZHwxfGRvd25sb2FkfHRlc3RGaWxlLndpdGhGb3J3YXJkU2xhc2g_LnR4dA==';
        //Underscore should replace / character
        $this->router->expects($this->once())
            ->method('generate')
            ->with(
                'oro_attachment_file',
                [
                    'codedString' => $expectsString,
                    'extension' => 'txt'
                ],
                true
            );
        $this->attachmentManager->getFileUrl($parentEntity, $fieldName, $this->attachment, 'download', true);
    }

    public function testDecodeAttachmentUrl()
    {
        $this->assertEquals(
            [
                'Oro\Test\TestClass',
                'testField',
                1,
                'download',
                'testFile.withForwardSlash?.txt'
            ],
            $this->attachmentManager->decodeAttachmentUrl(
                'T3JvXFRlc3RcVGVzdENsYXNzfHRlc3RGaWVsZHwxfGRvd25sb2FkfHRlc3RGaWxlLndpdGhGb3J3YXJkU2xhc2g/LnR4dA=='
            )
        );
    }

    public function testWrongAttachmentUrl()
    {
        $this->setExpectedException('\LogicException');
        $this->attachmentManager->decodeAttachmentUrl('bm90Z29vZHN0cmluZw==');
    }

    public function testNoneBase64AttachmentUrl()
    {
        $this->setExpectedException('\LogicException');
        $this->attachmentManager->decodeAttachmentUrl('bad string');
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
            ->setConstructorArgs([__DIR__.'/../Fixtures/testFile/test.txt'])
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

        $this->attachmentManager->preUpload($this->attachment); // delete should be called
        $this->attachmentManager->preUpload($this->attachment); // delete shouldn't be called

        $this->assertNull($this->attachment->getFilename());
        $this->assertNull($this->attachment->getExtension());
        $this->assertNull($this->attachment->getOriginalFilename());
    }

    public function testPreUpload()
    {
        $file = new File(__DIR__.'/../Fixtures/testFile/test.txt');

        $this->attachment
            ->setEmptyFile(false)
            ->setFile($file);

        $adapter = $this->getMockBuilder('Gaufrette\Adapter\Cache')
            ->disableOriginalConstructor()
            ->getMock();
        $this->filesystem->expects($this->any())
            ->method('getAdapter')
            ->will($this->returnValue($adapter));
        $adapter->expects($this->once())
            ->method('setMetadata');

        $this->attachmentManager->preUpload($this->attachment);

        $this->assertEquals('test.txt', $this->attachment->getOriginalFilename());
        $this->assertEquals('txt', $this->attachment->getExtension());
        $this->assertEquals('text/plain', $this->attachment->getMimeType());
        $this->assertEquals(9, $this->attachment->getFileSize());
    }

    public function testGetFilteredImageUrl()
    {
        $this->attachment->setId(1);
        $filerName = 'testFilter';
        $this->attachment->setOriginalFilename('test.doc');
        $this->router->expects($this->once())
            ->method('generate')
            ->with(
                'oro_filtered_attachment',
                [
                    'id' => 1,
                    'filename' => 'test.doc',
                    'filter' => $filerName
                ]
            );
        $this->attachmentManager->getFilteredImageUrl($this->attachment, $filerName);
    }

    /**
     * @dataProvider getData
     */
    public function testGetFileSize($value, $expected)
    {
        $this->assertEquals($expected, $this->attachmentManager->getFileSize($value));
    }

    public function getData()
    {
        return [
            [0, '0.00 B'],
            [pow(1024, 0), '1.00 B'],
            [pow(1024, 1), '1.02 KB'],
            [pow(1024, 2), '1.05 MB'],
            [pow(1024, 3), '1.07 GB'],
            [pow(1024, 4), pow(1024, 4)],
        ];
    }

    public function testFileKey()
    {
        $fileId           = 123;
        $ownerEntityClass = 'Acme\MyClass';
        $ownerEntityId    = 456;

        $key = $this->attachmentManager->buildFileKey($fileId, $ownerEntityClass, $ownerEntityId);
        $this->assertNotEmpty($key);
        $this->assertTrue(is_string($key));

        list($extractedFileId, $extractedOwnerEntityClass, $extractedOwnerEntityId) =
            $this->attachmentManager->parseFileKey($key);

        $this->assertSame($fileId, $extractedFileId);
        $this->assertSame($ownerEntityClass, $extractedOwnerEntityClass);
        $this->assertSame($ownerEntityId, $extractedOwnerEntityId);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Invalid file key: "Invalid Key".
     */
    public function testParseInvalidFileKey()
    {
        $this->attachmentManager->parseFileKey('Invalid Key');
    }

    public function testCopyLocalFileToStorage()
    {
        $localFilePath = __DIR__ . '/../Fixtures/testFile/test.txt';

        $newFileName = 'test2.txt';

        $resultStream = new InMemoryBuffer($this->filesystem, $newFileName);

        $this->filesystem->expects($this->once())
            ->method('createStream')
            ->with($newFileName)
            ->will($this->returnValue($resultStream));

        $this->attachmentManager->copyLocalFileToStorage($localFilePath, $newFileName);
        $resultStream->open(new StreamMode('rb+'));
        $resultStream->seek(0);

        $this->assertEquals('Test data', $resultStream->read(100));
    }

    public function testCopyAttachmentFile()
    {
        $localFilePath = __DIR__ . '/../Fixtures/testFile/test.txt';

        $sourceStream = new InMemoryBuffer($this->filesystem, $this->attachment->getFilename());
        $sourceStream->open(new StreamMode('wb+'));
        $sourceStream->write(file_get_contents($localFilePath));
        $sourceStream->seek(0);
        $sourceStream->close();

        $resultStream = new InMemoryBuffer($this->filesystem, 'test2.txt');

        $this->filesystem->expects($this->at(0))
            ->method('createStream')
            ->with($this->attachment->getFilename())
            ->will($this->returnValue($sourceStream));

        $this->filesystem->expects($this->at(1))
            ->method('createStream')
            ->with($this->anything())
            ->will($this->returnValue($resultStream));

        $newAttachment = $this->attachmentManager->copyAttachmentFile($this->attachment);

        $this->assertEquals($this->attachment->getOriginalFilename(), $newAttachment->getOriginalFilename());
        $this->assertNotEquals($this->attachment->getFilename(), $newAttachment->getFilename());

        $resultStream->open(new StreamMode('rb+'));
        $resultStream->seek(0);
        $this->assertEquals('Test data', $resultStream->read(100));
    }

    public function testIsImageType()
    {
        $this->assertTrue($this->attachmentManager->isImageType('image/png'));
        $this->assertFalse($this->attachmentManager->isImageType('application/pdf'));
    }

    public function testGetFileIcons()
    {
        $this->assertEquals(
            $this->attachmentManager->getFileIcons(),
            [
                'default' => 'icon_default',
                'txt' => 'icon_txt'
            ]
        );
    }
}
