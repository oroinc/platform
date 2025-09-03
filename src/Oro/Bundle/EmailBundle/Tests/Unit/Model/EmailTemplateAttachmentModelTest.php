<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Tests\Unit\Model;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Entity\FileItem;
use Oro\Bundle\EmailBundle\Model\EmailTemplateAttachmentModel;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use PHPUnit\Framework\TestCase;

final class EmailTemplateAttachmentModelTest extends TestCase
{
    use EntityTestCaseTrait;

    public function testConstruct(): void
    {
        $model = new EmailTemplateAttachmentModel();
        self::assertNull($model->getId());
        self::assertNull($model->getFile());
        self::assertCount(0, $model->getFileItems());
        self::assertNull($model->getFilePlaceholder());
        self::assertSame('', (string)$model);
    }

    public function testProperties(): void
    {
        self::assertPropertyAccessors(
            new EmailTemplateAttachmentModel(),
            [
                ['id', 42],
                ['file', new File()],
                ['filePlaceholder', 'entity.file'],
            ]
        );

        self::assertPropertyCollection(new EmailTemplateAttachmentModel(), 'fileItems', new FileItem());
    }

    public function testToStringWhenFile(): void
    {
        $model = new EmailTemplateAttachmentModel();

        $file = new File();
        $file->setFilename('sample_filename.txt');
        $model->setFile($file);
        self::assertSame($file->getFilename(), (string)$model);
    }

    public function testToStringWhenPlaceholder(): void
    {
        $model = new EmailTemplateAttachmentModel();

        $placeholder = 'sample_placeholder';
        $model->setFilePlaceholder($placeholder);
        self::assertSame($placeholder, (string)$model);
    }
}
