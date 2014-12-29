<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Entity;

use Symfony\Component\HttpFoundation\File\File as FileType;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Tests\Unit\Fixtures\TestUser;

class FileTest extends EntityTestAbstract
{
    protected function setUp()
    {
        $this->entity = new File();
    }

    /**
     * @return array
     */
    public function getSetDataProvider()
    {
        $filename = 'testFile.doc';
        $originalFileName = 'original.doc';
        $date = new \DateTime('now');
        $file = new FileType('testPath', false);
        $extension = 'txt';
        $type = 'text/doc';
        $fileSize = 10000;
        $owner = new TestUser();

        return [
            'filename' => ['filename', $filename, $filename],
            'originalFileName' => ['originalFileName', $originalFileName, $originalFileName],
            'createdAt' => ['createdAt', $date, $date],
            'updatedAt' => ['updatedAt', $date, $date],
            'file' => ['file', $file, $file],
            'extension' => ['extension', $extension, $extension],
            'mimeType' => ['mimeType', $type, $type],
            'fileSize' => ['fileSize', $fileSize, $fileSize],
            'owner'    => ['owner', $owner, $owner]
        ];
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
        $this->assertEquals('', $this->entity->__toString());
        $this->entity->setFilename('file.doc');
        $this->entity->setOriginalFilename('original.doc');
        $this->assertEquals('file.doc (original.doc)', $this->entity->__toString());
    }
}
