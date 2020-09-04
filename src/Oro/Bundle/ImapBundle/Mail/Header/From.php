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
 * This file is a copy of {@see Laminas\Mail\Header\From}
 */

namespace Oro\Bundle\ImapBundle\Mail\Header;

use Laminas\Mail\Header\Exception;
use Laminas\Mail\Header\From as BaseFrom;
use Oro\Bundle\ImapBundle\Mail\AddressList;
use Oro\Bundle\ImapBundle\Mail\Headers;

/**
 * From header that uses overridden OptionalAddressList as the storage of address list
 * and uses overridden GenericHeader class during parsing the header value.
 */
class From extends BaseFrom
{
    /**
     * {@inheritdoc}
     *
     * This method is a copy of {@see \Laminas\Mail\Header\From::fromString}
     * It is needed to override static call of `GenericHeader::splitHeaderLine`
     */
    public static function fromString($headerLine)
    {
        [$fieldName, $fieldValue] = GenericHeader::splitHeaderLine($headerLine);
        if (strtolower($fieldName) !== static::$type) {
            throw new Exception\InvalidArgumentException(sprintf(
                'Invalid header line for "%s" string',
                __CLASS__
            ));
        }

        // split value on ","
        $fieldValue = str_replace(Headers::FOLDING, ' ', $fieldValue);
        $fieldValue = preg_replace('/[^:]+:([^;]*);/', '$1,', $fieldValue);
        $values = str_getcsv($fieldValue, ',');

        $wasEncoded = false;
        array_walk(
            $values,
            function (&$value) use (&$wasEncoded) {
                $decodedValue = HeaderWrap::mimeDecodeValue($value);
                $wasEncoded = $wasEncoded || ($decodedValue !== $value);
                $value = trim($decodedValue);
                $value = self::stripComments($value);
                $value = preg_replace(
                    [
                        '#(?<!\\\)"(.*)(?<!\\\)"#', //quoted-text
                        '#\\\([\x01-\x09\x0b\x0c\x0e-\x7f])#' //quoted-pair
                    ],
                    [
                        '\\1',
                        '\\1'
                    ],
                    $value
                );
            }
        );
        $header = new static();
        if ($wasEncoded) {
            $header->setEncoding('UTF-8');
        }

        $values = array_filter($values);

        $addressList = $header->getAddressList();
        foreach ($values as $address) {
            $addressList->addFromString($address);
        }
        return $header;
    }

    /**
     * {@inheritdoc}
     *
     * This method is a copy of {@see \Laminas\Mail\Header\From::getAddressList}
     * It is needed to override `new AddressList()`
     */
    public function getAddressList()
    {
        if (null === $this->addressList) {
            $this->setAddressList(new AddressList());
        }

        return $this->addressList;
    }
}
