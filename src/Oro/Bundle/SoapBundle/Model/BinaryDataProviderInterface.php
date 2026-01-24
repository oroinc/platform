<?php

namespace Oro\Bundle\SoapBundle\Model;

/**
 * Defines the contract for objects that provide binary data.
 *
 * Implementing classes expose binary data that can be serialized and transmitted through SOAP API responses.
 */
interface BinaryDataProviderInterface
{
    /**
     * Returns data
     *
     * @return string
     */
    public function getData();
}
