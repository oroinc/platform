<?php

/**
 * This file is a copy of {@see Zend\Mail\Headers}
 *
 * @copyright Copyright (c) 2005-2013 Zend Technologies USA Inc. (http://www.zend.com)
 */
namespace Oro\Bundle\EmailBundle\Mail;

use \Zend\Mail\Exception\RuntimeException;
use \Zend\Mail\Headers as BaseHeaders;
use \Zend\Mail\Header\HeaderInterface;

class Headers extends BaseHeaders
{
    /**
     * Populates headers from string representation
     *
     * Parses a string for headers, and aggregates them, in order, in the
     * current instance, primarily as strings until they are needed (they
     * will be lazy loaded)
     *
     * @param  string $string
     * @param  string $EOL EOL string; defaults to {@link EOL}
     * @throws RuntimeException
     * @return Headers
     */
    public static function fromString($string, $EOL = self::EOL)
    {
        $headers     = new static();
        $currentLine = '';

        // iterate the header lines, some might be continuations
        foreach (explode($EOL, $string) as $line) {
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
        }
        if ($currentLine) {
            $headers->addHeaderLine($currentLine);
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
}
