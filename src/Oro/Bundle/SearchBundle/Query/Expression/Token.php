<?php

namespace Oro\Bundle\SearchBundle\Query\Expression;

/**
 * Represents a search query token.
 */
class Token
{
    public $value;
    public $type;
    public $cursor;

    public const EOF_TYPE         = 'end of expression';
    public const NAME_TYPE        = 'name';
    public const NUMBER_TYPE      = 'number';
    public const STRING_TYPE      = 'string';
    public const OPERATOR_TYPE    = 'operator';
    public const PUNCTUATION_TYPE = 'punctuation';
    public const KEYWORD_TYPE     = 'keyword';

    /**
     * Constructor.
     *
     * @param string $type   The type of the token
     * @param string $value  The token value
     * @param int    $cursor The cursor position in the source
     */
    public function __construct($type, $value, $cursor)
    {
        $this->type   = $type;
        $this->value  = $value;
        $this->cursor = $cursor;
    }

    /**
     * Returns a string representation of the token.
     *
     * @return string A string representation of the token
     */
    #[\Override]
    public function __toString()
    {
        return sprintf('%3d %-11s %s', $this->cursor, strtoupper($this->type), $this->value);
    }

    /**
     * Tests the current token for a type and/or a value.
     *
     * @param string      $type  The type to test
     * @param string|null $value The token value
     *
     * @return bool
     */
    public function test($type, $value = null)
    {
        return $this->type === $type && (null === $value || $this->value == $value);
    }
}
