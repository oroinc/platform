<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\EmbeddedImages;

use Oro\Bundle\EmailBundle\EmbeddedImages\EmbeddedImage;

class EmbeddedImageTest extends \PHPUnit\Framework\TestCase
{
    public function testGettersWhenOnlyContentProvided(): void
    {
        $embeddedImage = new EmbeddedImage('sample_content');

        self::assertEquals('sample_content', $embeddedImage->getEncodedContent());
        self::assertNull($embeddedImage->getFilename());
        self::assertNull($embeddedImage->getContentType());
        self::assertNull($embeddedImage->getEncoding());
    }

    public function testGetters(): void
    {
        $embeddedImage = new EmbeddedImage(
            'sample_content',
            'sample_filename',
            'sample-content/type',
            'sample_encoding'
        );

        self::assertEquals('sample_content', $embeddedImage->getEncodedContent());
        self::assertEquals('sample_filename', $embeddedImage->getFilename());
        self::assertEquals('sample-content/type', $embeddedImage->getContentType());
        self::assertEquals('sample_encoding', $embeddedImage->getEncoding());
    }
}
