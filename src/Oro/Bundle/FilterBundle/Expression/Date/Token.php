<?php

namespace Oro\Bundle\FilterBundle\Expression\Date;

/**
 * Represents a token in a date expression.
 *
 * This class encapsulates a single token parsed from a date expression string,
 * including its type (operator, integer, variable, punctuation, time, date, or day-month),
 * its value, and an optional string representation. Tokens are used by the date expression
 * lexer and parser to break down and analyze date filter expressions.
 */
class Token
{
    public const TYPE_OPERATOR    = 'TYPE_OPERATOR';
    public const TYPE_INTEGER     = 'TYPE_INTEGER';
    public const TYPE_VARIABLE    = 'TYPE_VARIABLE';
    public const TYPE_PUNCTUATION = 'TYPE_PUNCTUATION';
    public const TYPE_TIME        = 'TYPE_TIME';
    public const TYPE_DATE        = 'TYPE_DATE';
    public const TYPE_DAYMONTH    = 'TYPE_DAYMONTH';

    /** @var int */
    private $type;

    /** @var mixed */
    private $value;

    /** @var string */
    private $stringRepresentation;

    /**
     * @param string $type
     * @param mixed  $value
     * @param null   $stringRepresentation
     */
    public function __construct($type, $value, $stringRepresentation = null)
    {
        $this->type                 = $type;
        $this->value                = $value;
        $this->stringRepresentation = $stringRepresentation;
    }

    /**
     * Check whenever current token is instance of given type
     *
     * @param int   $type
     * @param mixed $value
     *
     * @return bool
     */
    public function is($type, $value = null)
    {
        return $this->getType() === $type && (null === $value || $this->getValue() == $value);
    }

    /**
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return null|string
     */
    #[\Override]
    public function __toString()
    {
        return (string)$this->stringRepresentation;
    }
}
