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
    private $id;

    /** @var array */
    private $values;

    public function __construct(ConfigIdInterface $id, array $values = [])
    {
        $this->id = $id;
        $this->values = $values;
    }

    #[\Override]
    public function getId()
    {
        return $this->id;
    }

    #[\Override]
    public function get($code, $strict = false, $default = null)
    {
        if (\array_key_exists($code, $this->values)) {
            return $this->values[$code];
        }

        if ($strict) {
            throw new RuntimeException(\sprintf('Value "%s" for %s', $code, $this->getId()->toString()));
        }

        return $default;
    }

    #[\Override]
    public function set($code, $value)
    {
        $this->values[$code] = $value;

        return $this;
    }

    #[\Override]
    public function remove($code)
    {
        unset($this->values[$code]);

        return $this;
    }

    #[\Override]
    public function has($code)
    {
        return \array_key_exists($code, $this->values);
    }

    #[\Override]
    public function is($code, $value = true)
    {
        if (!\array_key_exists($code, $this->values)) {
            return false;
        }

        $existingValue = $this->values[$code];
        if (null === $existingValue) {
            return false;
        }

        return $existingValue == $value;
    }

    #[\Override]
    public function in($code, array $values, $strict = false)
    {
        return \in_array($this->get($code), $values, $strict);
    }

    #[\Override]
    public function all(?\Closure $filter = null)
    {
        if (null === $filter) {
            return $this->values;
        }

        return \array_filter($this->values, $filter);
    }

    #[\Override]
    public function getValues()
    {
        return $this->values;
    }

    #[\Override]
    public function setValues($values)
    {
        $this->values = $values;

        return $this;
    }

    public function __serialize(): array
    {
        return [$this->id, $this->values];
    }

    public function __unserialize(array $serialized): void
    {
        [$this->id, $this->values] = $serialized;
    }

    /**
     * @param array $data Initialization array
     *
     * @return Config A new instance of a Config object
     */
    // phpcs:disable
    public static function __set_state($data)
    {
        return new Config($data['id'], $data['values']);
    }
    // phpcs:enable

    /**
     * Creates a new object that is a copy of the current instance.
     */
    public function __clone()
    {
        $this->id = clone $this->id;
        foreach ($this->values as $key => $value) {
            if (\is_object($value)) {
                $this->values[$key] = clone $value;
            }
        }
    }
}
