<?php

namespace Oro\Bundle\IntegrationBundle\Provider\Rest\Client;

use Oro\Bundle\IntegrationBundle\Provider\Rest\Exception\RestException;

interface RestResponseInterface
{
    /**
     * @return string
     */
    public function __toString();

    /**
     * Get the request url as string
     *
     * @return string
     */
    public function getRequestUrl();

    /**
     * Get the response body as string
     *
     * @return string
     */
    public function getBodyAsString();

    /**
     * Get the response status code
     *
     * @return integer
     */
    public function getStatusCode();

    /**
     * Get the entire response as a string
     *
     * @return string
     */
    public function getMessage();

    /**
     * Get the HTTP header
     *
     * @param string $header Case-sensitive Name of header
     * @return string Returns HTTP header
     */
    public function getHeader($header);

    /**
     * Get list of HTTP headers
     *
     * @return array
     */
    public function getHeaders();

    /**
     * Check response hasHTTP header
     *
     * @param string $header Case-sensitive Name of header
     * @return bool
     */
    public function hasHeader($header);

    /**
     * Get the response reason phrase- a human readable version of the numeric
     * status code
     *
     * @return string
     */
    public function getReasonPhrase();

    /**
     * Checks if HTTP Status code is a Client Error (4xx)
     *
     * @return bool
     */
    public function isClientError();

    /**
     * Checks if HTTP Status code is Server OR Client Error (4xx or 5xx)
     *
     * @return boolean
     */
    public function isError();

    /**
     * Checks if HTTP Status code is Information (1xx)
     *
     * @return bool
     */
    public function isInformational();

    /**
     * Checks if HTTP Status code is a Redirect (3xx)
     *
     * @return bool
     */
    public function isRedirect();

    /**
     * Checks if HTTP Status code is Server Error (5xx)
     *
     * @return bool
     */
    public function isServerError();

    /**
     * Checks if HTTP Status code is Successful (2xx | 304)
     *
     * @return bool
     */
    public function isSuccessful();

    /**
     * Parse the JSON response body and return an array
     *
     * @return array|string|int|bool|float
     * @throws RestException if the response body is not in JSON format
     */
    public function json();
}
