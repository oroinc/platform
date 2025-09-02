<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Tests\Unit\Entity;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Entity\EmailTemplateAttachment;
use Oro\Bundle\EmailBundle\Entity\EmailTemplateTranslation;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use PHPUnit\Framework\TestCase;

final class EmailTemplateAttachmentTest extends TestCase
{
    use EntityTestCaseTrait;

    public function testConstruct(): void
    {
        $entity = new EmailTemplateAttachment();
        self::assertNull($entity->getFile());
        self::assertNull($entity->getFilePlaceholder());
        self::assertSame('', (string)$entity);
    }

    public function testProperties(): void
    {
        self::assertPropertyAccessors(
            new EmailTemplateAttachment(),
            [
                ['file', new File()],
                ['filePlaceholder', 'entity.file'],
                ['template', new EmailTemplate()],
                ['translation', new EmailTemplateTranslation()],
            ]
        );
    }

    public function testToStringWhenFile(): void
    {
        $entity = new EmailTemplateAttachment();

        $file = new File();
        $file->setFilename('sample_filename.txt');
        $entity->setFile($file);
        self::assertSame($file->getFilename(), (string)$entity);
    }

    public function testToStringWhenPlaceholder(): void
    {
        $entity = new EmailTemplateAttachment();

        $placeholder = 'sample_placeholder';
        $entity->setFilePlaceholder($placeholder);
        self::assertSame($placeholder, (string)$entity);
    }
}
