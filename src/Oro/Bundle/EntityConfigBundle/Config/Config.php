<?php

namespace Oro\Bundle\EntityConfigBundle\Config;

use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Exception\RuntimeException;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

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
    public function get($code, $strict = false)
    {
        if (isset($this->values[$code])) {
            return $this->values[$code];
        }

        if ($strict) {
            throw new RuntimeException(sprintf('Value "%s" for %s', $code, $this->getId()->toString()));
        }

        return null;
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
    public function has($code)
    {
        return isset($this->values[$code]);
    }

    /**
     * {@inheritdoc}
     */
    public function is($code, $value = true)
    {
        return $this->get($code) === null ? false : $this->get($code) == $value;
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
