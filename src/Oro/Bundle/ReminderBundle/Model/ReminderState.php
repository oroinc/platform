<?php

namespace Oro\Bundle\ReminderBundle\Model;

use Oro\Bundle\ReminderBundle\Exception\InvalidArgumentException;

class ReminderState implements \ArrayAccess, \Serializable
{
    const SEND_TYPE_SENT = 'sent';
    const SEND_TYPE_NOT_SENT = 'not_sent';

    /**
     * @var array
     */
    protected $types;

    /**
     * @param array $types
     */
    public function __construct(array $types = [])
    {
        $this->types = $types;
    }

    /**
     * Get all send types names
     *
     * @return bool
     */
    public function isAllSent()
    {
        foreach ($this->types as $state) {
            if ($state !== self::SEND_TYPE_SENT) {
                return false;
            }
        }
        return true;
    }

    /**
     * Get all send types names
     *
     * @return array
     */
    public function getSendTypeNames()
    {
        return array_keys($this->types);
    }

    /**
     * Is reminder has send type state
     *
     * @param string $name
     * @return bool
     */
    public function hasSendTypeState($name)
    {
        return $this->offsetExists($name);
    }

    /**
     * Get reminder send type state
     *
     * @param string $name
     * @return string
     * @throws InvalidArgumentException
     */
    public function getSendTypeState($name)
    {
        return $this->offsetGet($name);
    }

    /**
     * Set reminder send type state
     *
     * @param string $name
     * @param string $state
     */
    public function setSendTypeState($name, $state)
    {
        $this->offsetSet($name, $state);
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return $this->types;
    }

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        return http_build_query($this->types);
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($serialized)
    {
        parse_str($serialized, $this->types);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset)
    {
        return isset($this->types[$offset]);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        if ($this->offsetExists($offset)) {
            return $this->types[$offset];
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value)
    {
        $this->types[$offset] = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        if ($this->offsetExists($offset)) {
            unset($this->types[$offset]);
        }
    }
}
