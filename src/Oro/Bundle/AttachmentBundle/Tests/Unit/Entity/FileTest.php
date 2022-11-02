<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Entity;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\HttpFoundation\File\File as ComponentFile;

class FileTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;
    use EntityTrait;

    private File $entity;

    protected function setUp(): void
    {
        $this->entity = new File();
    }

    public function testAccessors(): void
    {
        $properties = [
            ['id', 1],
            ['uuid', '123e4567-e89b-12d3-a456-426655440000', false],
            ['owner', new User()],
            ['filename', 'sample_filename'],
            ['extension', 'smplext'],
            ['mimeType', 'sample/mime-type'],
            ['originalFilename', 'sample_original_filename'],
            ['fileSize', 12345],
            ['parentEntityClass', \stdClass::class],
            ['parentEntityId', 2],
            ['parentEntityFieldName', 'sampleFieldName'],
            ['createdAt', new \DateTime('today')],
            ['updatedAt', new \DateTime('today')],
            ['file', new ComponentFile('sample/file', false)],
            ['emptyFile', true],
            ['externalUrl', 'example.org/file/path']
        ];

        self::assertPropertyAccessors($this->entity, $properties);
    }

    public function testPrePersists(): void
    {
        $testDate = new \DateTime('now', new \DateTimeZone('UTC'));

        $this->entity->prePersist();
        $this->entity->preUpdate();

        self::assertEquals($testDate->format('Y-m-d'), $this->entity->getCreatedAt()->format('Y-m-d'));
        self::assertEquals($testDate->format('Y-m-d'), $this->entity->getUpdatedAt()->format('Y-m-d'));
    }

    public function testEmptyFile(): void
    {
        self::assertNull($this->entity->isEmptyFile());
        $this->entity->setEmptyFile(true);
        self::assertTrue($this->entity->isEmptyFile());
    }

    public function testToString(): void
    {
        $this->entity->setFilename('file.doc');
        self::assertEquals('file.doc', $this->entity->__toString());

        $this->entity->setOriginalFilename('original.doc');
        self::assertEquals('file.doc (original.doc)', $this->entity->__toString());
    }

    public function testSerialize(): void
    {
        self::assertSame([null, null, $this->entity->getUuid(), null, null, null], $this->entity->__serialize());

        $file = $this->getEntity(
            File::class,
            [
                'id' => 1,
                'filename' => 'sample_filename',
                'uuid' => 'test-uuid',
                'externalUrl' => 'http://example.org/image.png',
                'originalFilename' => 'original-filename.png',
                'mimeType' => 'image/png',
            ]
        );
        self::assertEquals(
            [1, 'sample_filename', 'test-uuid', 'http://example.org/image.png', 'original-filename.png', 'image/png'],
            $file->__serialize()
        );
    }

    public function testUnserialize(): void
    {
        $this->entity->__unserialize([
            1,
            'sample_filename',
            'test-uuid',
            'http://example.org/image.png',
            'original-filename.png',
            'image/png'
        ]);
        $data = serialize($this->entity);
        /** @var File $entity */
        $entity = unserialize($data);
        self::assertSame('sample_filename', $entity->getFilename());
        self::assertSame(1, $entity->getId());
        self::assertSame('test-uuid', $entity->getUuid());
        self::assertEquals('http://example.org/image.png', $entity->getExternalUrl());
        self::assertEquals('original-filename.png', $entity->getOriginalFilename());
        self::assertEquals('image/png', $entity->getMimeType());
    }

    public function testClone(): void
    {
        /** @var File $file */
        $file = $this->getEntity(
            File::class,
            [
                'id' => 1,
                'uuid' => 'sample-uuid',
                'parentEntityClass' => \stdClass::class,
                'parentEntityFieldName' => 'sampleField',
                'parentEntityId' => 1,
            ]
        );

        $clonedFile = clone $file;

        self::assertNull($clonedFile->getId());
        self::assertNull($clonedFile->getUuid());
        self::assertNull($clonedFile->getParentEntityClass());
        self::assertNull($clonedFile->getParentEntityFieldName());
        self::assertNull($clonedFile->getParentEntityId());
    }
}
