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
     * Get the the raw message headers as a string
     *
     * @return string
     */
    public function getRawHeaders();

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
     * Get the Content-Encoding HTTP header
     *
     * @return string|null
     */
    public function getContentEncoding();

    /**
     * Get the Content-Language HTTP header
     *
     * @return string|null Returns the language the content is in.
     */
    public function getContentLanguage();

    /**
     * Get the Content-Length HTTP header
     *
     * @return integer Returns the length of the response body in bytes
     */
    public function getContentLength();

    /**
     * Get the Content-Location HTTP header
     *
     * @return string|null Returns an alternate location for the returned data (e.g /index.htm)
     */
    public function getContentLocation();

    /**
     * Get the Content-Disposition HTTP header
     *
     * @return string|null Returns the Content-Disposition header
     */
    public function getContentDisposition();

    /**
     * Get the Content-MD5 HTTP header
     *
     * @return string|null Returns a Base64-encoded binary MD5 sum of the content of the response.
     */
    public function getContentMd5();

    /**
     * Get the Content-Range HTTP header
     *
     * @return string Returns where in a full body message this partial message belongs (e.g. bytes 21010-47021/47022).
     */
    public function getContentRange();

    /**
     * Get the Content-Type HTTP header
     *
     * @return string Returns the mime type of this content.
     */
    public function getContentType();

    /**
     * Checks if the Content-Type is of a certain type.  This is useful if the
     * Content-Type header contains charset information and you need to know if
     * the Content-Type matches a particular type.
     *
     * @param string $type Content type to check against
     *
     * @return bool
     */
    public function isContentType($type);

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

    /**
     * Parse the XML response body and return a \SimpleXMLElement.
     *
     * In order to prevent XXE attacks, this method disables loading external
     * entities. If you rely on external entities, then you must parse the
     * XML response manually by accessing the response body directly.
     *
     * @return \SimpleXMLElement
     * @throws RestException if the response body is not in XML format
     * @link http://websec.io/2012/08/27/Preventing-XXE-in-PHP.html
     */
    public function xml();

    /**
     * Get the redirect count of this response
     *
     * @return int
     */
    public function getRedirectCount();

    /**
     * Get the effective URL that resulted in this response (e.g. the last redirect URL)
     *
     * @return string
     */
    public function getEffectiveUrl();
}
