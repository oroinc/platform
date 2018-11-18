<?php

/**
 * This file is a copy of {@see Zend\Mail\Header\From}
 *
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace Oro\Bundle\EmailBundle\Mail\Header;

use \Zend\Mail\Header\Exception;
use \Zend\Mail\Header\From as BaseFrom;
use Oro\Bundle\EmailBundle\Mail\AddressList;
use Oro\Bundle\EmailBundle\Mail\Headers;

class From extends BaseFrom
{
    /**
     * {@inheritdoc}
     */
    public static function fromString($headerLine)
    {
        list($fieldName, $fieldValue) = GenericHeader::splitHeaderLine($headerLine);
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
     */
    public function getAddressList()
    {
        if (null === $this->addressList) {
            $this->setAddressList(new AddressList());
        }

        return $this->addressList;
    }
}
