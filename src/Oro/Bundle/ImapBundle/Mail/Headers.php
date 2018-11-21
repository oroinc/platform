<?php

/**
 * This file is a copy of {@see Zend\Mail\Headers}
 *
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace Oro\Bundle\ImapBundle\Mail;

use \Zend\Mail\Exception\RuntimeException;
use \Zend\Mail\Header\HeaderInterface;
use \Zend\Mail\Headers as BaseHeaders;
use Oro\Bundle\ImapBundle\Exception\InvalidHeaderException;
use Oro\Bundle\ImapBundle\Exception\InvalidHeadersException;
use Oro\Bundle\ImapBundle\Mail\Header\GenericHeader;
use Oro\Bundle\ImapBundle\Mail\Header\HeaderLoader;

/**
 * Overridden zend-mail Headers class that simplifies the header checks and do not throw exceptions
 * to be able to process all email headers.
 */
class Headers extends BaseHeaders
{
    /**
     * {@inheritdoc}
     */
    public static function fromString($string, $EOL = self::EOL)
    {
        $headers = new static();
        $currentLine = '';

        // array with exceptions was thrown during header processing.
        $exceptions = [];

        // iterate the header lines, some might be continuations
        foreach (explode($EOL, $string) as $line) {
            try {
                // check if a header name is present
                if (preg_match('/^(?P<name>[\x21-\x39\x3B-\x7E]+):.*$/', $line, $matches)) {
                    if ($currentLine) {
                        // a header name was present, then store the current complete line
                        $headers->addHeaderLine($currentLine);
                    }
                    $currentLine = trim($line);
                } elseif (preg_match('/^\s+.*$/', $line, $matches)) {
                    // continuation: append to current line
                    $currentLine .= trim($line);
                } elseif (preg_match('/^\s*$/', $line)) {
                    // empty line indicates end of headers
                    break;
                } else {
                    // Line does not match header format!
                    throw new RuntimeException(sprintf(
                        'Line "%s"does not match header format!',
                        $line
                    ));
                }
            } catch (\Exception $e) {
                // avoid throwing an exception and collect it to be able to continue to parse headers.
                $exceptions[] = new InvalidHeaderException($currentLine, $e);
                $currentLine = trim($line);
            }
        }

        try {
            if ($currentLine) {
                $headers->addHeaderLine($currentLine);
            }
        } catch (\Exception $e) {
            $exceptions[] = new InvalidHeaderException($currentLine, $e);
        }

        // In case if there are invalid headers, wrap the exceptions and valid headers with InvalidHeadersException
        // to be able to log exceptions and continue to work with message.
        if (count($exceptions)) {
            throw new InvalidHeadersException($exceptions, $headers);
        }

        return $headers;
    }

    /**
     * {@inheritdoc}
     */
    public function addHeader(HeaderInterface $header)
    {
        $key = $this->normalizeFieldName($header->getFieldName());
        // do not duplicate contenttype index
        if ($key !== 'contenttype' || ($key === 'contenttype' && !in_array($key, $this->headersKeys, true))) {
            $this->headersKeys[] = $key;
            $this->headers[] = $header;
        }
        if ($this->getEncoding() !== 'ASCII') {
            $header->setEncoding($this->getEncoding());
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function normalizeFieldName($fieldName)
    {
        return parent::normalizeFieldName($fieldName);
    }

    /**
     * {@inheritdoc}
     */
    public function getPluginClassLoader()
    {
        if ($this->pluginClassLoader === null) {
            $this->pluginClassLoader = new HeaderLoader();
        }

        return $this->pluginClassLoader;
    }

    /**
     * {@inheritdoc}
     */
    public function loadHeader($headerLine)
    {
        list($name, ) = GenericHeader::splitHeaderLine($headerLine);
        /** @var HeaderInterface $class */
        $class = $this->getPluginClassLoader()->load($name) ?: GenericHeader::class;
        return $class::fromString($headerLine);
    }
}
