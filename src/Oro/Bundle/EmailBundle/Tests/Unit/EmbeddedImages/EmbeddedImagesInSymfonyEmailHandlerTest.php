<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\EmbeddedImages;

use Oro\Bundle\EmailBundle\EmbeddedImages\EmbeddedImage;
use Oro\Bundle\EmailBundle\EmbeddedImages\EmbeddedImagesExtractor;
use Oro\Bundle\EmailBundle\EmbeddedImages\EmbeddedImagesInSymfonyEmailHandler;
use Symfony\Component\Mime\Email as SymfonyEmail;
use Symfony\Component\Mime\Part\DataPart;

class EmbeddedImagesInSymfonyEmailHandlerTest extends \PHPUnit\Framework\TestCase
{
    private EmbeddedImagesExtractor|\PHPUnit\Framework\MockObject\MockObject $embeddedImagesExtractor;

    private EmbeddedImagesInSymfonyEmailHandler $handler;

    protected function setUp(): void
    {
        $this->embeddedImagesExtractor = $this->createMock(EmbeddedImagesExtractor::class);

        $this->handler = new EmbeddedImagesInSymfonyEmailHandler($this->embeddedImagesExtractor);
    }

    public function testHandleEmbeddedImagesDoesNothingWhenNoHtmlBody(): void
    {
        $symfonyEmail = (new SymfonyEmail())
            ->text('sample_text');

        $this->handler->handleEmbeddedImages($symfonyEmail);

        self::assertEquals([], $symfonyEmail->getAttachments());
        self::assertEquals('sample_text', $symfonyEmail->getTextBody());
        self::assertEquals('', $symfonyEmail->getHtmlBody());
    }

    public function testHandleEmbeddedImagesDoesNothingWhenNoEmbeddedImages(): void
    {
        $symfonyEmail = (new SymfonyEmail())
            ->html('sample_text');

        $this->embeddedImagesExtractor
            ->expects(self::once())
            ->method('extractEmbeddedImages')
            ->willReturn([]);

        $this->handler->handleEmbeddedImages($symfonyEmail);

        self::assertEquals([], $symfonyEmail->getAttachments());
        self::assertEquals('sample_text', $symfonyEmail->getHtmlBody());
        self::assertEquals('', $symfonyEmail->getTextBody());
    }

    public function testHandleEmbeddedImages(): void
    {
        $symfonyEmail = (new SymfonyEmail())
            ->html('sample_text');

        $embeddedImage = new EmbeddedImage(
            base64_encode('sample_content'),
            'sample_filename',
            'sample-content/type',
            'base64'
        );

        $this->embeddedImagesExtractor
            ->expects(self::once())
            ->method('extractEmbeddedImages')
            ->willReturnCallback(static function (&$body) use ($embeddedImage) {
                $body .= '_changed';

                return [$embeddedImage];
            });

        $dataPart = new DataPart('sample_content', $embeddedImage->getFilename(), $embeddedImage->getContentType());
        $dataPart->asInline();

        $this->handler->handleEmbeddedImages($symfonyEmail);

        self::assertEquals([$dataPart], $symfonyEmail->getAttachments());
        self::assertEquals('sample_text_changed', $symfonyEmail->getHtmlBody());
        self::assertEquals('', $symfonyEmail->getTextBody());
    }
}
