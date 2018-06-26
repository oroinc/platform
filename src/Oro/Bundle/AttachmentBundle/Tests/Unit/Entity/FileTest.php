<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Entity;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Tests\Unit\Fixtures\TestUser;
use Symfony\Component\HttpFoundation\File\File as FileType;

class FileTest extends \PHPUnit\Framework\TestCase
{
    /** @var File */
    protected $entity;

    protected function setUp()
    {
        $this->entity = new File();
    }

    public function testFilename()
    {
        $this->assertNull($this->entity->getFilename());
        $fileName = 'test.doc';
        $this->entity->setFilename($fileName);
        $this->assertEquals($fileName, $this->entity->getFilename());
    }

    public function testOriginalFilename()
    {
        $this->assertNull($this->entity->getOriginalFilename());
        $fileName = 'test.doc';
        $this->entity->setOriginalFilename($fileName);
        $this->assertEquals($fileName, $this->entity->getOriginalFilename());
    }

    public function testCreatedAt()
    {
        $this->assertNull($this->entity->getCreatedAt());
        $date = new \DateTime('now');
        $this->entity->setCreatedAt($date);
        $this->assertEquals($date, $this->entity->getCreatedAt());
    }

    public function testUpdatedAt()
    {
        $this->assertNull($this->entity->getUpdatedAt());
        $date = new \DateTime('now');
        $this->entity->setUpdatedAt($date);
        $this->assertEquals($date, $this->entity->getUpdatedAt());
    }

    public function testFile()
    {
        $this->assertNull($this->entity->getFile());
        $file = new FileType('testPath', false);
        $this->entity->setFile($file);
        $this->assertSame($file, $this->entity->getFile());
    }

    public function testExtension()
    {
        $this->assertNull($this->entity->getExtension());
        $extension = 'ext';
        $this->entity->setExtension($extension);
        $this->assertEquals($extension, $this->entity->getExtension());
    }

    public function testMimeType()
    {
        $this->assertNull($this->entity->getMimeType());
        $mimeType = 'text/plain';
        $this->entity->setMimeType($mimeType);
        $this->assertEquals($mimeType, $this->entity->getMimeType());
    }

    public function testFileSize()
    {
        $this->assertNull($this->entity->getFileSize());
        $fileSize = 10000;
        $this->entity->setFileSize($fileSize);
        $this->assertSame($fileSize, $this->entity->getFileSize());
    }

    public function testOwner()
    {
        $this->assertNull($this->entity->getOwner());
        $owner = new TestUser();
        $this->entity->setOwner($owner);
        $this->assertSame($owner, $this->entity->getOwner());
    }

    public function testPrePersists()
    {
        $testDate = new \DateTime('now', new \DateTimeZone('UTC'));

        $this->entity->prePersist();
        $this->entity->preUpdate();

        $this->assertEquals($testDate->format('Y-m-d'), $this->entity->getCreatedAt()->format('Y-m-d'));
        $this->assertEquals($testDate->format('Y-m-d'), $this->entity->getUpdatedAt()->format('Y-m-d'));
    }

    public function testEmptyFile()
    {
        $this->assertNull($this->entity->isEmptyFile());
        $this->entity->setEmptyFile(true);
        $this->assertTrue($this->entity->isEmptyFile());
    }

    public function testToString()
    {
        $this->assertSame('', $this->entity->__toString());
        $this->entity->setFilename('file.doc');
        $this->entity->setOriginalFilename('original.doc');
        $this->assertEquals('file.doc (original.doc)', $this->entity->__toString());
    }
}
