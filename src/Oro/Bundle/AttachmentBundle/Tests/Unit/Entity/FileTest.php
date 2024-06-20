<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Entity;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Symfony\Component\HttpFoundation\File\File as ComponentFile;

class FileTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

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

        self::assertPropertyAccessors(new File(), $properties);
    }

    public function testPrePersists(): void
    {
        $testDate = new \DateTime('now', new \DateTimeZone('UTC'));

        $entity = new File();
        $entity->prePersist();
        $entity->preUpdate();

        self::assertEquals($testDate->format('Y-m-d'), $entity->getCreatedAt()->format('Y-m-d'));
        self::assertEquals($testDate->format('Y-m-d'), $entity->getUpdatedAt()->format('Y-m-d'));
    }

    public function testEmptyFile(): void
    {
        $entity = new File();
        self::assertNull($entity->isEmptyFile());
        $entity->setEmptyFile(true);
        self::assertTrue($entity->isEmptyFile());
    }

    public function testToString(): void
    {
        $entity = new File();
        $entity->setFilename('file.doc');
        self::assertEquals('file.doc', $entity->__toString());

        $entity->setOriginalFilename('original.doc');
        self::assertEquals('file.doc (original.doc)', $entity->__toString());
    }

    public function testSerialize(): void
    {
        $entity = new File();
        self::assertSame([null, null, $entity->getUuid(), null, null, null], $entity->__serialize());

        $file = new File();
        ReflectionUtil::setId($file, 1);
        $file->setUuid('test-uuid');
        $file->setFilename('sample_filename');
        $file->setExternalUrl('http://example.org/image.png');
        $file->setOriginalFilename('original-filename.png');
        $file->setMimeType('image/png');
        self::assertEquals(
            [1, 'sample_filename', 'test-uuid', 'http://example.org/image.png', 'original-filename.png', 'image/png'],
            $file->__serialize()
        );
    }

    public function testUnserialize(): void
    {
        $entity = new File();
        $entity->__unserialize([
            1,
            'sample_filename',
            'test-uuid',
            'http://example.org/image.png',
            'original-filename.png',
            'image/png'
        ]);
        $data = serialize($entity);
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
        $file = new File();
        ReflectionUtil::setId($file, 1);
        $file->setUuid('sample-uuid');
        $file->setParentEntityClass(\stdClass::class);
        $file->setParentEntityFieldName('sampleField');
        $file->setParentEntityId(1);

        $clonedFile = clone $file;

        self::assertNull($clonedFile->getId());
        self::assertNull($clonedFile->getUuid());
        self::assertNull($clonedFile->getParentEntityClass());
        self::assertNull($clonedFile->getParentEntityFieldName());
        self::assertNull($clonedFile->getParentEntityId());
    }
}
