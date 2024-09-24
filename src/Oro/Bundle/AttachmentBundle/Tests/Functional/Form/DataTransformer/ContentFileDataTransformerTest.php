<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Functional\Form\DataTransformer;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Form\DataTransformer\ContentFileDataTransformer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\File as HttpFile;

final class ContentFileDataTransformerTest extends TestCase
{
    private ContentFileDataTransformer $transformer;

    #[\Override]
    protected function setUp(): void
    {
        $this->transformer = new ContentFileDataTransformer();
    }

    public function testReverseTransform(): void
    {
        $tmpfname = tempnam('/tmp', 'config.json');

        $handle = fopen($tmpfname, 'w');
        fwrite($handle, 'content');
        fclose($handle);

        $value = new File();
        $value->setEmptyFile(false);
        $value->setFile(new HttpFile($tmpfname));

        self::assertEquals('content', $this->transformer->reverseTransform($value));
    }
}
