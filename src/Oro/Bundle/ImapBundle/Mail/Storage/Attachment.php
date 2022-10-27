<?php

namespace Oro\Bundle\ImapBundle\Mail\Storage;

use Laminas\Mail\Header\ContentType;
use Laminas\Mail\Headers;
use Laminas\Mail\Storage\Part;
use Laminas\Mime\Decode;

/**
 * Represent email attachment
 */
class Attachment
{
    /**
     * @var Part
     */
    protected $part;

    /**
     * @param Part $part The message part contains the attachment
     */
    public function __construct(Part $part)
    {
        $this->part = $part;
    }

    /**
     * Gets the headers collection
     *
     * @return Headers
     */
    public function getHeaders()
    {
        return $this->part->getHeaders();
    }

    /**
     * Gets a header in specified format
     *
     * @param  string $name   The name of header, matches case-insensitive, but camel-case is replaced with dashes
     * @param  string $format change The type of return value to 'string' or 'array'
     * @return Headers
     */
    public function getHeader($name, $format = null)
    {
        return $this->part->getHeader($name, $format);
    }

    /**
     * Gets the attached file name
     *
     * @return Value
     */
    public function getFileName()
    {
        $value   = '';
        $headers = $this->part->getHeaders();
        if ($headers->has('Content-Disposition')) {
            $contentDisposition = $this->part->getHeader('Content-Disposition');
            $value              = Decode::splitContentType($contentDisposition->getFieldValue(), 'filename');
            $encoding           = $contentDisposition->getEncoding();
        }
        if (empty($value) && $headers->has('Content-Type')) {
            /** @var ContentType $contentType */
            $contentType = $this->part->getHeader('Content-Type');
            $value       = $contentType->getParameter('name');
            $encoding    = $contentType->getEncoding();
        }
        if (empty($encoding)) {
            $encoding = 'ASCII';
        }

        // Extract name from quoted text. {@see \Oro\Bundle\ImapBundle\Mail\Headers::fromString}
        if (preg_match('"([^\\"]+)"', $value, $result)) {
            $value = $result[0];
        }

        return new Value($value, $encoding);
    }

    /**
     * Gets the attached file size
     *
     * @return int size
     */
    public function getFileSize()
    {
        return $this->part->getSize();
    }

    /**
     * Gets the attachment content
     *
     * @return Content
     */
    public function getContent()
    {
        if ($this->part->getHeaders()->has('Content-Type')) {
            /** @var ContentType $contentTypeHeader */
            $contentTypeHeader = $this->part->getHeader('Content-Type');
            $contentType       = $contentTypeHeader->getType();
            $charset           = $contentTypeHeader->getParameter('charset');
            $encoding          = $charset !== null ? $charset : 'ASCII';
        } else {
            $contentType = 'text/plain';
            $encoding    = 'ASCII';
        }

        return new Content($this->part->getContent(), $contentType, $this->getContentTransferEncoding(), $encoding);
    }

    /**
     * @return string|null
     */
    public function getEmbeddedContentId()
    {
        $contentIdValue = $this->getContentIdValue();
        if ($contentIdValue !== null) {
            $contentDisposition = $this->getContentDispositionValue();
            if (!$contentDisposition || Decode::splitContentType($contentDisposition, 'type') === 'inline') {
                return substr($contentIdValue, 1, strlen($contentIdValue) - 2);
            }
        }

        return null;
    }

    /**
     * @return null|string
     */
    protected function getContentIdValue()
    {
        return $this->part->getHeaders()->has('Content-ID')
            ? $this->part->getHeader('Content-ID')->getFieldValue()
            : null;
    }

    /**
     * @return null|string
     */
    protected function getContentDispositionValue()
    {
        return $this->part->getHeaders()->has('Content-Disposition')
            ? $this->part->getHeader('Content-Disposition')->getFieldValue()
            : null;
    }

    protected function getContentTransferEncoding(): string
    {
        if ($this->part->getHeaders()->has('Content-Transfer-Encoding')) {
            $contentTransferEncodings = $this->part->getHeader('Content-Transfer-Encoding', 'array');

            return end($contentTransferEncodings);
        }

        return 'BINARY';
    }
}
