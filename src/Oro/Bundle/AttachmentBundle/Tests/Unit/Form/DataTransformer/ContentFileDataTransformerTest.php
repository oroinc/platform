<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Form\DataTransformer;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Form\DataTransformer\ContentFileDataTransformer;
use PHPUnit\Framework\TestCase;

final class ContentFileDataTransformerTest extends TestCase
{
    private ContentFileDataTransformer $transformer;

    protected function setUp(): void
    {
        $this->transformer = new ContentFileDataTransformer();
    }

    public function testTransformWithDefaultFileName(): void
    {
        $value = 'content of file';

        $file = new File();
        $file->setFilename('file.json');
        $file->setFileSize(mb_strlen($value));
        $file->setEmptyFile(false);
        $file->setUpdatedAt(null);

        $actual = $this->transformer->transform($value);
        $actual->setUpdatedAt(null);

        self::assertEquals($file, $actual);
    }

    public function testTransformWithDefinedFileName(): void
    {
        $value = 'content of file';

        $file = new File();
        $file->setFilename('config.json');
        $file->setFileSize(mb_strlen($value));
        $file->setEmptyFile(false);
        $file->setUpdatedAt(null);

        $this->transformer->setFileName('config.json');

        $actual = $this->transformer->transform($value);
        $actual->setUpdatedAt(null);

        self::assertEquals($file, $actual);
    }

    public function testTransformNull(): void
    {
        self::assertEquals(null, $this->transformer->transform(null));
    }

    public function testReverseTransformNull(): void
    {
        self::assertEquals(null, $this->transformer->reverseTransform(null));
    }

    public function testReverseTransformEmptyFile(): void
    {
        $this->transformer->transform('');

        self::assertEquals(null, $this->transformer->reverseTransform(new File()));
    }

    public function testReverseTransformFileWithoutContent(): void
    {
        $this->transformer->transform('content');

        $value = (new File())->setEmptyFile(false);

        self::assertEquals('content', $this->transformer->reverseTransform($value));
    }

    public function testReverseTransformWithEmptySystemConfig(): void
    {
        $this->transformer->transform('');

        $value = (new File())->setEmptyFile(false);

        self::assertEquals(null, $this->transformer->reverseTransform($value));
    }
}
