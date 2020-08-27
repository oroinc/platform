<?php

/**
 * Copyright (c) 2020 Laminas Project a Series of LF Projects, LLC.
 *
 * Redistribution and use in source and binary forms, with or without modification, are permitted provided that the
 * following conditions are met:
 * - Redistributions of source code must retain the above copyright notice, this list of conditions and the following
 * disclaimer.
 * - Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the
 * following disclaimer in the documentation and/or other materials provided with the distribution.
 * - Neither the name of Laminas Foundation nor the names of its contributors may be used to endorse or promote
 * products derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES,
 * INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
 * SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY,
 * WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE
 * USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This file is a copy of {@see Laminas\Mail\Headers}
 */

namespace Oro\Bundle\ImapBundle\Mail;

use Exception;
use Laminas\Mail\Exception\RuntimeException;
use Laminas\Mail\Header\HeaderInterface;
use Laminas\Mail\Headers as BaseHeaders;
use Oro\Bundle\ImapBundle\Exception\InvalidHeaderException;
use Oro\Bundle\ImapBundle\Exception\InvalidHeadersException;
use Oro\Bundle\ImapBundle\Mail\Header\GenericHeader;
use Oro\Bundle\ImapBundle\Mail\Header\HeaderLoader;

/**
 * Overridden laminas-mail Headers class that simplifies the header checks and do not throw exceptions
 * to be able to process all email headers.
 */
class Headers extends BaseHeaders
{
    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
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
                    throw new RuntimeException(
                        sprintf(
                            'Line "%s"does not match header format!',
                            $line
                        )
                    );
                }
            } catch (Exception $e) {
                // avoid throwing an exception and collect it to be able to continue to parse headers.
                $exceptions[] = new InvalidHeaderException($currentLine, $e);
                $currentLine = trim($line);
            }
        }

        try {
            if ($currentLine) {
                $headers->addHeaderLine($currentLine);
            }
        } catch (Exception $e) {
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
        [$name,] = GenericHeader::splitHeaderLine($headerLine);
        /** @var HeaderInterface $class */
        $class = $this->getPluginClassLoader()->load($name) ?: GenericHeader::class;
        return $class::fromString($headerLine);
    }
}
