<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Functional\Form\DataTransformer;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Form\DataTransformer\ContentFileDataTransformer;
use Oro\Component\Testing\TempDirExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\File as HttpFile;

final class ContentFileDataTransformerTest extends TestCase
{
    use TempDirExtension;

    private ContentFileDataTransformer $transformer;

    #[\Override]
    protected function setUp(): void
    {
        $this->transformer = new ContentFileDataTransformer();
    }

    public function testReverseTransform(): void
    {
        $tmpFilePath = $this->getTempFile('attachment_content_file_data_transformer', 'config', '.json');

        file_put_contents($tmpFilePath, 'content');

        $value = new File();
        $value->setEmptyFile(false);
        $value->setFile(new HttpFile($tmpFilePath));

        self::assertEquals('content', $this->transformer->reverseTransform($value));
    }
}
