<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Entity;

use Symfony\Component\HttpFoundation\File\File;

use Oro\Bundle\AttachmentBundle\Entity\Attachment;
use Oro\Bundle\UserBundle\Entity\User;

class AttachmentTest extends \PHPUnit_Framework_TestCase
{
    /** @var Attachment */
    protected $entity;

    protected function setUp()
    {
        $this->entity = new Attachment();
    }

    public function tearDown()
    {
        unset($this->entity);
    }

    /**
     * @dataProvider  getSetDataProvider
     *
     * @param string $property
     * @param mixed $value
     * @param mixed $expected
     */
    public function testSetGet($property, $value = null, $expected = null)
    {
        if ($value !== null) {
            call_user_func_array(array($this->entity, 'set' . ucfirst($property)), [$value]);
        }

        $this->assertEquals($expected, call_user_func_array(array($this->entity, 'get' . ucfirst($property)), []));
    }

    /**
     * @return array
     */
    public function getSetDataProvider()
    {
        $filename = 'testFile.doc';
        $originalFileName = 'original.doc';
        $date = new \DateTime('now');
        $file = new File('testPath', false);
        $extension = 'txt';
        $type = 'text/doc';
        $fileSize = 10000;
        $comment = 'test comment';

        return [
            'filename' => ['filename', $filename, $filename],
            'originalFileName' => ['originalFileName', $originalFileName, $originalFileName],
            'createdAt' => ['createdAt', $date, $date],
            'updatedAt' => ['updatedAt', $date, $date],
            'file' => ['file', $file, $file],
            'extension' => ['extension', $extension, $extension],
            'mimeType' => ['mimeType', $type, $type],
            'fileSize' => ['fileSize', $fileSize, $fileSize],
            'comment' => ['comment', $comment, $comment]
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
