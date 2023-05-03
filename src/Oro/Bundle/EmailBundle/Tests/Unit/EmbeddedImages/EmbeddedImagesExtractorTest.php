<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\EmbeddedImages;

use Oro\Bundle\EmailBundle\EmbeddedImages\EmbeddedImage;
use Oro\Bundle\EmailBundle\EmbeddedImages\EmbeddedImagesExtractor;
use Symfony\Component\Mime\MimeTypes;

class EmbeddedImagesExtractorTest extends \PHPUnit\Framework\TestCase
{
    private EmbeddedImagesExtractor $extractor;

    protected function setUp(): void
    {
        $mimeTypes = new MimeTypes(['image/png' => ['png']]);
        $this->extractor = new EmbeddedImagesExtractor($mimeTypes);
    }

    /**
     * @dataProvider extractEmbeddedImagesDataProvider
     */
    public function testExtractEmbeddedImages(
        string $content,
        string $expectedContent,
        array $expectedEmbeddedImages
    ): void {
        $embeddedImages = $this->extractor->extractEmbeddedImages($content);

        foreach ($expectedEmbeddedImages as [$encodedContent, $contentType, $encoding]) {
            /** @var EmbeddedImage $embeddedImage */
            $embeddedImage = array_shift($embeddedImages);
            self::assertEquals($encodedContent, $embeddedImage->getEncodedContent());
            self::assertEquals($contentType, $embeddedImage->getContentType());
            self::assertEquals($encoding, $embeddedImage->getEncoding());

            $expectedContent = str_replace(
                '%' . $encodedContent . '_cid%',
                $embeddedImage->getFilename(),
                $expectedContent
            );
        }

        self::assertEquals($expectedContent, $content);
    }

    public function extractEmbeddedImagesDataProvider(): array
    {
        return [
            'empty content' => [
                'content' => '',
                'expectedContent' => '',
                'expectedEmbeddedImages' => [],
            ],
            'no images' => [
                'content' => '<foo>sample</foo> content',
                'expectedContent' => '<foo>sample</foo> content',
                'expectedEmbeddedImages' => [],
            ],
            'regular image' => [
                'content' => '<img alt="regular_image" src="http://example.org/regular-image.png"/>',
                'expectedContent' => '<img alt="regular_image" src="http://example.org/regular-image.png"/>',
                'expectedEmbeddedImages' => [],
            ],
            'embedded img when not image content type' => [
                'content' => '<img src="data:text/csv;base64,encoded_content">',
                'expectedContent' => '<img src="data:text/csv;base64,encoded_content">',
                'expectedEmbeddedImages' => [],
            ],
            'embedded img with image content type' => [
                'content' => '<img src="data:image/png;base64,encoded_content">',
                'expectedContent' => '<img src="cid:%encoded_content_cid%">',
                'expectedEmbeddedImages' => [['encoded_content', 'image/png', 'base64']],
            ],
            'embedded img with attributes and image content type' => [
                'content' => '<img alt="sample-attr" id="sample-id" src="data:image/png;base64,encoded_content">',
                'expectedContent' => '<img alt="sample-attr" id="sample-id" src="cid:%encoded_content_cid%">',
                'expectedEmbeddedImages' => [['encoded_content', 'image/png', 'base64']],
            ],
            'complex content with multiple embedded images' => [
                'content' => '<foo>Sample text</foo><img alt="regular image"' . PHP_EOL .
                    ' src="http://example.com/image.png"><img  alt="sample-attr"' .
                    ' id="sample-id"  src="data:image/png;base64,encoded_content" ' .
                    '/><img/><img  src="data:image/png;base64,encoded_content2"  >',
                'expectedContent' => '<foo>Sample text</foo><img alt="regular image"' . PHP_EOL .
                    ' src="http://example.com/image.png"><img  alt="sample-attr"' .
                    ' id="sample-id"  src="cid:%encoded_content_cid%" ' .
                    '/><img/><img  src="cid:%encoded_content2_cid%"  >',
                'expectedEmbeddedImages' => [
                    ['encoded_content', 'image/png', 'base64'],
                    ['encoded_content2', 'image/png', 'base64'],
                ],
            ],
        ];
    }
}
