<?php

namespace Oro\Component\ChainProcessor;

/**
 * The base class for check whether a value of a specific processor attribute
 * is matched a corresponding value from the execution context.
 */
abstract class AbstractMatcher
{
    public const OPERATOR_AND = '&';
    public const OPERATOR_OR  = '|';
    public const OPERATOR_NOT = '!';

    /**
     * Checks if a value of a processor attribute matches a corresponding value
     * from the execution context.
     *
     * @param mixed  $value        Array or Scalar
     * @param mixed  $contextValue Array or Scalar
     * @param string $name         The name of an attribute
     *
     * @return bool
     */
    protected function isMatch($value, $contextValue, $name)
    {
        if ($contextValue instanceof ToArrayInterface) {
            return $this->isMatchAnyInArray($value, $contextValue->toArray(), $name);
        }

        return \is_array($contextValue)
            ? $this->isMatchAnyInArray($value, $contextValue, $name)
            : $this->isMatchAnyWithScalar($value, $contextValue, $name);
    }

    /**
     * @param mixed  $value        Array or Scalar
     * @param mixed  $contextValue Array
     * @param string $name         The name of an attribute
     *
     * @return bool
     */
    protected function isMatchAnyInArray($value, $contextValue, $name)
    {
        if (!\is_array($value)) {
            return $this->isMatchScalarInArray($value, $contextValue, $name);
        }

        switch (\key($value)) {
            case self::OPERATOR_NOT:
                return !$this->isMatchScalarInArray(\current($value), $contextValue, $name);
            case self::OPERATOR_AND:
                $result = true;
                foreach (\current($value) as $val) {
                    if (!$this->isMatchScalarWithArray($val, $contextValue, $name)) {
                        $result = false;
                        break;
                    }
                }

                return $result;
            case self::OPERATOR_OR:
                $result = false;
                foreach (\current($value) as $val) {
                    if ($this->isMatchScalarWithArray($val, $contextValue, $name)) {
                        $result = true;
                        break;
                    }
                }

                return $result;
        }

        return false;
    }

    /**
     * @param mixed  $value        Scalar or ['!' => Scalar]
     * @param mixed  $contextValue Array
     * @param string $name         The name of an attribute
     *
     * @return bool
     */
    protected function isMatchScalarWithArray($value, $contextValue, $name)
    {
        if (!\is_array($value)) {
            return $this->isMatchScalarInArray($value, $contextValue, $name);
        }

        return self::OPERATOR_NOT === \key($value)
            ? !$this->isMatchScalarInArray(\current($value), $contextValue, $name)
            : false;
    }

    /**
     * @param mixed  $value        Scalar
     * @param mixed  $contextValue Array
     * @param string $name         The name of an attribute
     *
     * @return bool
     */
    protected function isMatchScalarInArray($value, $contextValue, $name)
    {
        return \in_array($value, $contextValue, true);
    }

    /**
     * @param mixed  $value        Array or Scalar
     * @param mixed  $contextValue Scalar
     * @param string $name         The name of an attribute
     *
     * @return bool
     */
    protected function isMatchAnyWithScalar($value, $contextValue, $name)
    {
        if (!\is_array($value)) {
            return $this->isMatchScalars($value, $contextValue, $name);
        }

        switch (\key($value)) {
            case self::OPERATOR_NOT:
                return !$this->isMatchScalars(\current($value), $contextValue, $name);
            case self::OPERATOR_AND:
                $result = true;
                foreach (\current($value) as $val) {
                    if (!$this->isMatchScalarWithScalar($val, $contextValue, $name)) {
                        $result = false;
                        break;
                    }
                }

                return $result;
            case self::OPERATOR_OR:
                $result = false;
                foreach (\current($value) as $val) {
                    if ($this->isMatchScalarWithScalar($val, $contextValue, $name)) {
                        $result = true;
                        break;
                    }
                }

                return $result;
        }

        return false;
    }

    /**
     * @param mixed  $value        Scalar or ['!' => Scalar]
     * @param mixed  $contextValue Scalar
     * @param string $name         The name of an attribute
     *
     * @return bool
     */
    protected function isMatchScalarWithScalar($value, $contextValue, $name)
    {
        if (!\is_array($value)) {
            return $this->isMatchScalars($value, $contextValue, $name);
        }

        return self::OPERATOR_NOT === \key($value)
            ? !$this->isMatchScalars(\current($value), $contextValue, $name)
            : false;
    }

    /**
     * @param mixed  $value        Scalar
     * @param mixed  $contextValue Scalar
     * @param string $name         The name of an attribute
     *
     * @return bool
     */
    protected function isMatchScalars($value, $contextValue, $name)
    {
        return $contextValue === $value;
    }
}
