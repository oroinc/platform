<?php

namespace Oro\Component\ChainProcessor;

/**
 * This applicable checker allows to use processor attributes to manage processors to be executed.
 * For example if you need to execute some processor only for specified entity type,
 * you can add 'class' attribute to this processor. As result it will be executed only if
 * a value of the 'class' attribute is equal of a value of 'class' attribute in the Context.
 * When several attributes are defined for a processor, it will be executed only if
 * values all of these attributes are equal to the corresponding attributes in the Context.
 */
class MatchApplicableChecker implements ApplicableCheckerInterface
{
    const OPERATOR_AND = '&';
    const OPERATOR_OR  = '|';
    const OPERATOR_NOT = '!';

    /** @var string[] */
    protected $ignoredAttributes;

    /**
     * @param string[] $ignoredAttributes
     */
    public function __construct(array $ignoredAttributes = ['group'])
    {
        $this->ignoredAttributes = $ignoredAttributes;
    }

    /**
     * @param string $attribute
     */
    public function addIgnoredAttribute($attribute)
    {
        $this->ignoredAttributes[] = $attribute;
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable(ContextInterface $context, array $processorAttributes)
    {
        $result = self::APPLICABLE;
        foreach ($processorAttributes as $name => $value) {
            if (in_array($name, $this->ignoredAttributes, true)
                || (!is_scalar($value) && !is_array($value))
            ) {
                continue;
            }
            if (!$context->has($name)) {
                $result = self::ABSTAIN;
            } elseif (!$this->isMatch($value, $context->get($name), $name)) {
                $result = self::NOT_APPLICABLE;
                break;
            }
        }

        return $result;
    }

    /**
     * Checks if a value of a processor attribute matches a corresponding value from the context
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

        return is_array($contextValue)
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
        if (!is_array($value)) {
            return $this->isMatchScalarInArray($value, $contextValue, $name);
        }

        $operator = key($value);
        if (self::OPERATOR_NOT === $operator) {
            $result = !$this->isMatchScalarInArray(current($value), $contextValue, $name);
        } elseif (self::OPERATOR_AND === $operator) {
            $result = true;
            foreach (current($value) as $val) {
                if (!$this->isMatchScalarWithArray($val, $contextValue, $name)) {
                    $result = false;
                    break;
                }
            }
        } elseif (self::OPERATOR_OR === $operator) {
            $result = false;
            foreach (current($value) as $val) {
                if ($this->isMatchScalarWithArray($val, $contextValue, $name)) {
                    $result = true;
                    break;
                }
            }
        } else {
            $result = false;
        }

        return $result;
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
        if (!is_array($value)) {
            return $this->isMatchScalarInArray($value, $contextValue, $name);
        }

        return self::OPERATOR_NOT === key($value)
            ? !$this->isMatchScalarInArray(current($value), $contextValue, $name)
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
        return in_array($value, $contextValue, true);
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
        if (!is_array($value)) {
            return $this->isMatchScalars($value, $contextValue, $name);
        }

        $operator = key($value);
        if (self::OPERATOR_NOT === $operator) {
            $result = !$this->isMatchScalars(current($value), $contextValue, $name);
        } elseif (self::OPERATOR_AND === $operator) {
            $result = true;
            foreach (current($value) as $val) {
                if (!$this->isMatchScalarWithScalar($val, $contextValue, $name)) {
                    $result = false;
                    break;
                }
            }
        } elseif (self::OPERATOR_OR === $operator) {
            $result = false;
            foreach (current($value) as $val) {
                if ($this->isMatchScalarWithScalar($val, $contextValue, $name)) {
                    $result = true;
                    break;
                }
            }
        } else {
            $result = false;
        }

        return $result;
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
        if (!is_array($value)) {
            return $this->isMatchScalars($value, $contextValue, $name);
        }

        return self::OPERATOR_NOT === key($value)
            ? !$this->isMatchScalars(current($value), $contextValue, $name)
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
