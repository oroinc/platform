<?php

namespace Oro\Bundle\EntityConfigBundle\Config;

use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;
use Oro\Bundle\EntityConfigBundle\Exception\RuntimeException;

/**
 * The aim of this class is to store configuration data for each configurable object (entity or field).
 * IMPORTANT: A performance of this class is very crucial, be careful during a refactoring.
 */
class Config implements ConfigInterface
{
    /** @var ConfigIdInterface */
    protected $id;

    /** @var array */
    protected $values;

    /**
     * @param ConfigIdInterface $id
     * @param array             $values
     */
    public function __construct(ConfigIdInterface $id, array $values = [])
    {
        $this->id     = $id;
        $this->values = $values;
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
        if (array_key_exists($code, $this->values)) {
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
        return array_key_exists($code, $this->values);
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
    public function getValues()
    {
        return $this->values;
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
        return serialize([$this->id, $this->values]);
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
        return new Config($data['id'], $data['values']);
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
