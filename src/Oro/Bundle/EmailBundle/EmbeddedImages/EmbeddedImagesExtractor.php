<?php

namespace Oro\Bundle\EmailBundle\EmbeddedImages;

use Symfony\Component\Mime\MimeTypesInterface;

/**
 * Finds embedded images, extracts and replaces them with content id references.
 */
class EmbeddedImagesExtractor
{
    private MimeTypesInterface $mimeTypes;

    public function __construct(MimeTypesInterface $mimeTypes)
    {
        $this->mimeTypes = $mimeTypes;
    }

    /**
     * Finds embedded images, extracts and replaces them with content id references.
     *
     * @param string $content
     * @return EmbeddedImage[]
     */
    public function extractEmbeddedImages(string &$content): array
    {
        $embeddedImages = [];
        $content = preg_replace_callback(
            '/<img(?P<attrs>.*)src(?:\s*)=(?:\s*)["\'](?P<src>.*)["\']/U',
            function ($matches) use (&$embeddedImages) {
                if (!empty($matches['src']) && str_starts_with($matches['src'], 'data:image')) {
                    [$mime, $data] = explode(';', $matches['src']);
                    [$encoding, $encodedContent] = explode(',', $data);
                    $mime = str_replace('data:', '', $mime);
                    $extensions = $this->mimeTypes->getExtensions($mime);
                    $fileName = sprintf('%s.%s', uniqid('', true), \array_shift($extensions));

                    $embeddedImages[$fileName] = new EmbeddedImage(
                        $encodedContent,
                        $fileName,
                        $mime,
                        $encoding
                    );

                    return sprintf('<img%ssrc="cid:%s"', $matches['attrs'], $fileName);
                }

                return $matches[0];
            },
            $content
        );

        return $embeddedImages;
    }
}
