<?php
namespace Oro\Component\MessageQueue\Transport;

/**
 * The Message interface is the root interface of all transport messages.
 * Most message-oriented middleware (MOM) products
 * treat messages as lightweight entities that consist of a header and a payload.
 * The header contains fields used for message routing and identification;
 * the payload contains the application data being sent.
 *
 * Within this general form, the definition of a message varies significantly across products.
 *
 * @link https://docs.oracle.com/javaee/1.4/api/javax/jms/Message.html
 */
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
     * @return array [name => value, ...]
     */
    public function getProperties();

    /**
     * @param string $name
     * @param mixed $default
     *
     * @return mixed
     */
    public function getProperty($name, $default = null);

    /**
     * @param array $headers
     *
     * @return void
     */
    public function setHeaders(array $headers);

    /**
     * @return array [name => value, ...]
     */
    public function getHeaders();

    /**
     * @param string $name
     * @param mixed $default
     *
     * @return mixed
     */
    public function getHeader($name, $default = null);

    /**
     * @param boolean $redelivered
     */
    public function setRedelivered($redelivered);

    /**
     * Gets an indication of whether this message is being redelivered.
     * The message is considered as redelivered,
     * when it was sent by a broker to consumer but consumer does not ACK or REJECT it.
     * The broker brings the message back to the queue and mark it as redelivered.
     *
     * @return boolean
     */
    public function isRedelivered();
}
