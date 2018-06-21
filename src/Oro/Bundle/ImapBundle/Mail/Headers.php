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
use Oro\Bundle\ImapBundle\Mail\Header\HeaderLoader;

/**
 * The Zend Framework zend-mail package provides more strictly rules for email headers.
 * To simplify checks they need to be overridden as the zend-mail is used only for import emails, and it is assumed
 * that if email exists on the mail server it has passed all checks and can be safety imported.
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
}
