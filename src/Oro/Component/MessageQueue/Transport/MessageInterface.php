<?php
namespace Oro\Component\MessageQueue\Transport;

interface MessageInterface
{
    /**
     * @return string
     */
    public function getBody();

    /**
     * @param string $body
     *
     * @return void
     */
    public function setBody($body);

    /**
     * @param array $properties
     *
     * @return void
     */
    public function setProperties(array $properties);

    /**
     * @return array
     */
    public function getProperties();

    /**
     * @param string $name
     * @param mixed $default
     *
     * @return string
     */
    public function getProperty($name, $default = null);

    /**
     * @param array $headers
     *
     * @return void
     */
    public function setHeaders(array $headers);

    /**
     * @return array
     */
    public function getHeaders();

    /**
     * @param string $name
     * @param mixed $default
     *
     * @return string
     */
    public function getHeader($name, $default = null);

    /**
     * @param boolean $redelivered
     */
    public function setRedelivered($redelivered);

    /**
     * @return boolean
     */
    public function isRedelivered();
}
