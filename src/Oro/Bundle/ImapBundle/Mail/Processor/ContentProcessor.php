<?php

namespace Oro\Bundle\ImapBundle\Mail\Processor;

use Zend\Mail\Header\ContentType;
use Zend\Mail\Storage\Part\PartInterface;

use Oro\Bundle\ImapBundle\Mail\Storage\Content;

class ContentProcessor
{
    /**
     * @param PartInterface $part
     *
     * @return Content
     */
    public function processText(PartInterface $part)
    {
        if ($part->isMultipart()) {
            return $this->getMultipartContentRecursively($part, 'text/plain');
        }

        return $this->extractContent($part);
    }

    /**
     * @param PartInterface $part
     *
     * @return Content|null
     */
    public function processHtml(PartInterface $part)
    {
        if (!$part->isMultipart()) {
            return null;
        }

        $content = $this->getMultipartContentRecursively($part, 'text/html');
        if (!$contents = $this->loadContents($part)) {
            return $content;
        }

        $replacedContent = strtr($content->getContent(), $contents);

        return new Content(
            $replacedContent,
            $content->getContentType(),
            $content->getContentTransferEncoding(),
            $content->getEncoding()
        );
    }

    /**
     * @param PartInterface $multipart
     * @param string        $format
     *
     * @return Content
     */
    protected function getMultipartContentRecursively(PartInterface $multipart, $format)
    {
        if ($multipart->isMultipart()) {
            foreach ($multipart as $part) {
                if ($part->isMultipart()) {
                    return $this->getMultipartContentRecursively($part, $format);
                }

                $contentTypeHeader = $this->getPartContentType($part);
                if ($contentTypeHeader->getType() === $format) {
                    return $this->extractContent($part);
                }
            }
        }
    }

    /**
     * Extracts body content from the given part
     *
     * @param PartInterface $part The message part where the content is stored
     *
     * @return Content
     */
    protected function extractContent(PartInterface $part)
    {
        /** @var ContentType $contentTypeHeader */
        $contentTypeHeader = $this->getPartContentType($part);
        if ($contentTypeHeader !== null) {
            $contentType = $contentTypeHeader->getType();
            $charset     = $contentTypeHeader->getParameter('charset');
            $encoding    = $charset !== null ? $charset : 'ASCII';
        } else {
            $contentType = 'text/plain';
            $encoding    = 'ASCII';
        }

        if ($part->getHeaders()->has('Content-Transfer-Encoding')) {
            $contentTransferEncoding = $part->getHeader('Content-Transfer-Encoding')->getFieldValue();
        } else {
            $contentTransferEncoding = 'BINARY';
        }

        return new Content($part->getContent(), $contentType, $contentTransferEncoding, $encoding);
    }

    /**
     * Gets the Content-Type for the given part
     *
     * @param PartInterface $part The message part
     *
     * @return ContentType|null
     */
    protected function getPartContentType(PartInterface $part)
    {
        return $part->getHeaders()->has('Content-Type') ? $part->getHeader('Content-Type') : null;
    }

    /**
     * @param PartInterface $multipart
     *
     * @return array
     */
    protected function loadContents(PartInterface $multipart)
    {
        /** @var ContentIdExtractorInterface[] $contentExtractors */
        $contentExtractors = [new ImageExtractor()];

        $contents = [];
        foreach ($contentExtractors as $contentExtractor) {
            $contents = array_merge($contents, $contentExtractor->extract($multipart));
        }

        return $contents;
    }
}
