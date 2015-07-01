<?php

namespace Oro\Bundle\ImapBundle\Mail\Storage;

use Zend\Mail\Headers;
use Zend\Mail\Storage\Part;

use Oro\Bundle\ImapBundle\Mail\Processor\ContentProcessor;
use Oro\Bundle\ImapBundle\Mail\Storage\Exception\InvalidBodyFormatException;

class Body
{
    const FORMAT_TEXT = false;
    const FORMAT_HTML = true;

    /**
     * @var Part
     */
    protected $part;

    /**
     * @param Part $part The message part contains the message body
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
     * Gets a string contains the message body content
     *
     * @param bool $format The required format of the body. Can be FORMAT_TEXT or FORMAT_HTML
     * @return Content
     * @throws InvalidBodyFormatException
     */
    public function getContent($format = Body::FORMAT_TEXT)
    {
        $contentProcessor = new ContentProcessor();
        $content          = null;
        switch ($format) {
            case Body::FORMAT_HTML:
                $content = $contentProcessor->processHtml($this->part);
                break;
            case Body::FORMAT_TEXT:
                $content = $contentProcessor->processText($this->part);
                break;
        }

        if ($content) {
            return $content;
        }

        throw new InvalidBodyFormatException(
            sprintf(
                'A messages does not have %s content.',
                $format === Body::FORMAT_TEXT ? 'TEXT' : 'HTML'
            )
        );
    }
}
