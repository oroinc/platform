<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Manager;

use Oro\Bundle\AttachmentBundle\Exception\InvalidAttachmentEncodedParametersException;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\AttachmentBundle\Tests\Unit\Fixtures\TestAttachment;
use Oro\Bundle\AttachmentBundle\Tests\Unit\Fixtures\TestClass;
use Oro\Bundle\EntityExtendBundle\Entity\Manager\AssociationManager;
use Symfony\Component\Routing\RouterInterface;

class AttachmentManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var AttachmentManager  */
    protected $attachmentManager;

    /** @var  \PHPUnit\Framework\MockObject\MockObject|RouterInterface */
    protected $router;

    /** @var  \PHPUnit\Framework\MockObject\MockObject|AssociationManager */
    protected $associationManager;

    /** @var TestAttachment */
    protected $attachment;

    /** @var array */
    protected $fileIcons;

    public function setUp()
    {
        $this->router = $this->getMockBuilder('Symfony\Bundle\FrameworkBundle\Routing\Router')
            ->disableOriginalConstructor()
            ->getMock();

        $this->fileIcons = [
            'default' => 'icon_default',
            'txt' => 'icon_txt'
        ];

        $this->attachment = new TestAttachment();
        $this->attachment->setFilename('testFile.txt');
        $this->attachment->setOriginalFilename('testFile.txt');

        $this->associationManager = $this
            ->getMockBuilder('Oro\Bundle\EntityExtendBundle\Entity\Manager\AssociationManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->attachmentManager = new AttachmentManager(
            $this->router,
            $this->fileIcons,
            $this->associationManager,
            true,
            true
        );
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
                AttachmentManager::ATTACHMENT_FILE_ROUTE,
                [
                    'codedString' => $expectsString,
                    'extension' => 'txt'
                ],
                RouterInterface::ABSOLUTE_URL
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

    public function testDecodeAttachmentUrlException()
    {
        $this->expectException(InvalidAttachmentEncodedParametersException::class);
        $this->expectExceptionMessage('Attachment parameters cannot be decoded');

        $this->attachmentManager->decodeAttachmentUrl('some_string');
    }

    public function testWrongAttachmentUrl()
    {
        $this->expectException('\LogicException');
        $this->attachmentManager->decodeAttachmentUrl('bm90Z29vZHN0cmluZw==');
    }

    public function testNoneBase64AttachmentUrl()
    {
        $this->expectException('\LogicException');
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

    public function testGetFilteredImageUrl()
    {
        $this->attachment->setId(1);
        $filerName = 'testFilter';
        $this->attachment->setFilename('test.doc');
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
