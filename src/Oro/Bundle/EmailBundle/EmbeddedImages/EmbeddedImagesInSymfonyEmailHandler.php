<?php

namespace Oro\Bundle\EmailBundle\EmbeddedImages;

use Oro\Bundle\EmailBundle\Decoder\ContentDecoder;
use Symfony\Component\Mime\Email as SymfonyEmail;

/**
 * Extracts embedded images from {@see SymfonyEmail} html body and adds corresponding attachments.
 */
class EmbeddedImagesInSymfonyEmailHandler
{
    private EmbeddedImagesExtractor $embeddedImagesExtractor;

    public function __construct(EmbeddedImagesExtractor $embeddedImagesExtractor)
    {
        $this->embeddedImagesExtractor = $embeddedImagesExtractor;
    }

    public function handleEmbeddedImages(SymfonyEmail $symfonyEmail): void
    {
        $htmlBody = $symfonyEmail->getHtmlBody();
        if (!$htmlBody) {
            return;
        }

        $embeddedImages = $this->embeddedImagesExtractor->extractEmbeddedImages($htmlBody);
        if (!$embeddedImages) {
            return;
        }

        foreach ($embeddedImages as $embeddedImage) {
            $decodedContent = ContentDecoder::decode(
                $embeddedImage->getEncodedContent(),
                $embeddedImage->getEncoding()
            );
            $symfonyEmail->embed($decodedContent, $embeddedImage->getFilename(), $embeddedImage->getContentType());
        }

        $symfonyEmail->html($htmlBody);
    }
}
