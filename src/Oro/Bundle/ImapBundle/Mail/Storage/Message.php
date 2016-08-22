<?php

namespace Oro\Bundle\ImapBundle\Mail\Storage;

use \Zend\Mail\Header\ContentType;
use \Zend\Mail\Header\HeaderInterface;
use \Zend\Mail\Storage\Part;
use \Zend\Stdlib\ErrorHandler;
use \Zend\Mime\Mime as BaseMime;
use \Zend\Mail\Storage\AbstractStorage;
use \Zend\Mail\Storage\Exception\InvalidArgumentException;
use \Zend\Mail\Storage\Exception\RuntimeException;

use Oro\Bundle\EmailBundle\Mail\Headers;
use Oro\Bundle\EmailBundle\Mime\Decode;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class Message extends \Zend\Mail\Storage\Message
{
    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function __construct(array $params)
    {
        if (isset($params['file'])) {
            if (!is_resource($params['file'])) {
                ErrorHandler::start();
                $params['raw'] = file_get_contents($params['file']);
                $error = ErrorHandler::stop();
                if ($params['raw'] === false) {
                    throw new RuntimeException('could not open file', 0, $error);
                }
            } else {
                $params['raw'] = stream_get_contents($params['file']);
            }
        }

        if (!empty($params['flags'])) {
            // set key and value to the same value for easy lookup
            $this->flags = array_combine($params['flags'], $params['flags']);
        }

        if (isset($params['handler'])) {
            if (!$params['handler'] instanceof AbstractStorage) {
                throw new InvalidArgumentException('handler is not a valid mail handler');
            }
            if (!isset($params['id'])) {
                throw new InvalidArgumentException('need a message id with a handler');
            }

            $this->mail       = $params['handler'];
            $this->messageNum = $params['id'];
        }

        $params['strict'] = isset($params['strict']) ? $params['strict'] : false;

        if (isset($params['raw'])) {
            Decode::splitMessage(
                $params['raw'],
                $this->headers,
                $this->content,
                BaseMime::LINEEND,
                $params['strict']
            );
        } elseif (isset($params['headers'])) {
            if (is_array($params['headers'])) {
                $this->headers = new Headers();
                $this->headers->addHeaders($params['headers']);
            } else {
                if (empty($params['noToplines'])) {
                    Decode::splitMessage($params['headers'], $this->headers, $this->topLines);
                } else {
                    $this->headers = Headers::fromString($params['headers']);
                }
            }

            if (isset($params['content'])) {
                $this->content = $params['content'];
            }
        }
    }

    /**
     * Gets the message attachments
     *
     * @return Body
     * @throws \Zend\Mail\Storage\Exception\RuntimeException
     */
    public function getBody()
    {
        return new Body($this);
    }

    /**
     * Gets the message attachments
     *
     * @return Attachment[]
     */
    public function getAttachments()
    {
        return $this->isMultipart()
            ? $this->getMultiPartAttachments($this)
            : [];
    }

    /**
     * @return null|ContentType
     */
    public function getPriorContentType()
    {
        if ($this->isMultipart()) {
            return $this->getMultiPartPriorContentType($this);
        } else {
            return $this->getPartContentType($this);
        }
    }

    /**
     * Fix incorrect create headers object in ZF Mime\Decode::splitMessage
     *
     * {@inheritdoc}
     */
    public function getHeaders()
    {
        parent::getHeaders();

        if (!$this->headers instanceof Headers) {
            $this->headers = new Headers();
        }

        return $this->headers;
    }

    /**
     * @return null|Attachment
     */
    public function getMessageAsAttachment()
    {
        return $this->getPartAttachment($this);
    }

    /**
     * Gets the Content-Type for the given part
     *
     * @param Part $part The message part
     *
     * @return \Zend\Mail\Header\ContentType|null
     */
    protected function getPartContentType($part)
    {
        return $part->getHeaders()->has('Content-Type')
            ? $part->getHeader('Content-Type')
            : null;
    }

    /**
     * Gets the Content-Disposition for the given part
     *
     * @param Part $part   The message part
     * @param bool $format Can be FORMAT_RAW or FORMAT_ENCODED, see HeaderInterface::FORMAT_* constants
     *
     * @return string|null
     */
    protected function getPartContentDisposition($part, $format = HeaderInterface::FORMAT_RAW)
    {
        return $part->getHeaders()->has('Content-Disposition')
            ? $part->getHeader('Content-Disposition')->getFieldValue($format)
            : null;
    }

    /**
     * @param Part $multiPart
     * @return bool|null|ContentType
     */
    protected function getMultiPartPriorContentType(Part $multiPart)
    {
        $textContentType = false;
        foreach ($multiPart as $part) {
            $contentType = $part->isMultipart()
                ? $this->getMultiPartPriorContentType($part)
                : $this->getPartContentType($part);
            if ($contentType) {
                $type = strtolower($contentType->getType());
                if ($type === 'text/html') {
                    // html is preferred part
                    return $contentType;
                } elseif ($type === 'text/plain') {
                    $textContentType = $contentType;
                }
            }
        }
        if ($textContentType) {
            // in case when only text part presents
            return $textContentType;
        } else {
            return null;
        }
    }

    /**
     * @param Part $multiPart
     * @return Attachment[]
     */
    protected function getMultiPartAttachments(Part $multiPart)
    {
        $result = [];
        foreach ($multiPart as $part) {
            if ($part->isMultipart()) {
                $result = array_merge($this->getMultiPartAttachments($part), $result);
            } else {
                $attachment = $this->getPartAttachment($part);
                if ($attachment !== null) {
                    $result[] = $attachment;
                }
            }
        }

        return $result;
    }

    /**
     * The 'Content-Disposition' may be missed, because it is introduced only in RFC 2183.
     * Param 'name' of 'Content-type' may be missed too.
     *
     * So, it's assumed that any part that has 'Content-Disposition' OR param ";name=" in the Content-Type
     * is an attachment.
     *
     * @param Part $part
     *
     * @return null|Attachment
     */
    protected function getPartAttachment(Part $part)
    {
        $contentType = $this->getPartContentType($part);
        if ($contentType !== null) {
            $name               = $contentType->getParameter('name');
            $contentDisposition = $this->getPartContentDisposition($part);
            if ($name !== null || $contentDisposition !== null) {
                return new Attachment($part);
            }
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getPart($num)
    {
        if (isset($this->parts[$num])) {
            return $this->parts[$num];
        }

        if (!$this->mail && $this->content === null) {
            throw new RuntimeException('part not found');
        }

        if ($this->mail && $this->mail->hasFetchPart) {
            // TODO: fetch part
            // return
        }

        $this->cacheContent();

        if (!isset($this->parts[$num])) {
            throw new RuntimeException('part not found');
        }

        return $this->parts[$num];
    }

    /**
     * {@inheritdoc}
     */
    public function countParts()
    {
        if ($this->countParts) {
            return $this->countParts;
        }

        $this->countParts = count($this->parts);
        if ($this->countParts) {
            return $this->countParts;
        }

        if ($this->mail && $this->mail->hasFetchPart) {
            // TODO: fetch part
            // return
        }

        $this->cacheContent();

        $this->countParts = count($this->parts);
        return $this->countParts;
    }

    /**
     * Cache content and split in parts if multipart
     *
     * @throws RuntimeException
     * @return null
     */
    protected function cacheContent()
    {
        // caching content if we can't fetch parts
        if ($this->content === null && $this->mail) {
            $this->content = $this->mail->getRawContent($this->messageNum);
        }

        if (!$this->isMultipart()) {
            return;
        }

        // split content in parts
        $boundary = $this->getHeaderField('content-type', 'boundary');
        if (!$boundary) {
            throw new RuntimeException('no boundary found in content type to split message');
        }
        $parts = Decode::splitMessageStruct($this->content, $boundary);
        if ($parts === null) {
            return;
        }
        $counter = 1;
        foreach ($parts as $part) {
            $this->parts[$counter++] = new static(array('headers' => $part['header'], 'content' => $part['body']));
        }
    }
}
