<?php

namespace Oro\Bundle\EntityConfigBundle\Config;

use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;
use Oro\Bundle\EntityConfigBundle\Exception\RuntimeException;

/**
 * The aim of this class is to store configuration data for each configurable object (entity or field).
 */
class Config implements ConfigInterface
{
    /**
     * @var ConfigIdInterface
     */
    protected $id;

    /**
     * @var array
     */
    protected $values = array();

    /**
     * Constructor.
     *
     * @param ConfigIdInterface $id
     */
    public function __construct(ConfigIdInterface $id)
    {
        $this->id = $id;
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function get($code, $strict = false, $default = null)
    {
        if ($this->has($code)) {
            return $this->values[$code];
        }

        if ($strict) {
            throw new RuntimeException(sprintf('Value "%s" for %s', $code, $this->getId()->toString()));
        }

        return $default;
    }

    /**
     * {@inheritdoc}
     */
    public function set($code, $value)
    {
        $this->values[$code] = $value;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function remove($code)
    {
        unset($this->values[$code]);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function has($code)
    {
        return isset($this->values[$code]) || array_key_exists($code, $this->values);
    }

    /**
     * {@inheritdoc}
     */
    public function is($code, $value = true)
    {
        $existingValue = $this->get($code);

        return $existingValue === null
            ? false
            : $existingValue == $value;
    }

    /**
     * {@inheritdoc}
     */
    public function in($code, array $values, $strict = false)
    {
        return in_array($this->get($code), $values, $strict);
    }

    /**
     * {@inheritdoc}
     */
    public function all(\Closure $filter = null)
    {
        return $filter
            ? array_filter($this->values, $filter)
            : $this->values;
    }

    /**
     * {@inheritdoc}
     */
    public function setValues($values)
    {
        $this->values = $values;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        return serialize(array($this->id, $this->values));
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($serialized)
    {
        list($this->id, $this->values) = unserialize($serialized);
    }

    /**
     * The __set_state handler
     *
     * @param array $data Initialization array
     * @return Config A new instance of a Config object
     */
    // @codingStandardsIgnoreStart
    public static function __set_state($data)
    {
        $result         = new Config($data['id']);
        $result->values = $data['values'];

        return $result;
    }
    // @codingStandardsIgnoreEnd

    /**
     * Creates a new object that is a copy of the current instance.
     */
    public function __clone()
    {
        $this->id     = clone $this->id;
        $this->values = array_map(
            function ($value) {
                return is_object($value) ? clone $value : $value;
            },
            $this->values
        );
    }
}
