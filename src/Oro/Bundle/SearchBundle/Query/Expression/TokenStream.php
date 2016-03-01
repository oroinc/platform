<?php

namespace Oro\Bundle\SearchBundle\Query\Expression;

use Oro\Bundle\SearchBundle\Exception\ExpressionSyntaxError;

class TokenStream
{
    /** @var Token */
    public $current;

    /** @var Token[] */
    private $tokens;

    /** @var int */
    private $position = 0;

    /**
     * Constructor.
     *
     * @param array $tokens An array of tokens
     */
    public function __construct(array $tokens)
    {
        $this->tokens  = $tokens;
        $this->current = $tokens[0];
    }

    /**
     * Returns a string representation of the token stream.
     *
     * @return string
     */
    public function __toString()
    {
        return implode("\n", $this->tokens);
    }

    /**
     * Sets the pointer to the next token.
     */
    public function next()
    {
        if (!isset($this->tokens[$this->position])) {
            throw new ExpressionSyntaxError('Unexpected end of expression', $this->current->cursor);
        }

        ++$this->position;

        $this->current = $this->tokens[$this->position];
    }

    /**
     * Tests a token with value.
     * If passed - returns token and sets the pointer to the text token.
     * If NOT passed throws Exception (in strict mode) or return FALSE otherwise
     *
     * @param string            $type
     * @param string|array|null $value
     * @param string|null       $message
     * @param bool              $strict  If TRUE - Exception will be thrown
     *
     * @return Token|bool
     */
    public function expect($type, $value = null, $message = null, $strict = true)
    {
        $token  = $this->current;
        $passed = false;

        if (!is_array($value)) {
            $value = [$value];
        }

        foreach ($value as $valueItem) {
            $passed = $token->test($type, $valueItem);
            if (true === $passed) {
                break;
            }
        }

        if (!$passed) {
            if ($strict) {
                throw new ExpressionSyntaxError(
                    sprintf(
                        '%sUnexpected token "%s" of value "%s" ("%s" expected%s)',
                        $message ? $message . '. ' : '',
                        $token->type,
                        $token->value,
                        $type,
                        $value ? sprintf(' with value "%s"', implode(', ', $value)) : ''
                    ),
                    $token->cursor
                );
            } else {
                return false;
            }
        }

        $this->next();

        return $token;
    }

    /**
     * Checks if end of stream was reached
     *
     * @return bool
     */
    public function isEOF()
    {
        return $this->current->type === Token::EOF_TYPE;
    }

    /**
     * Sets the pointer to the previous token.
     */
    public function prev()
    {
        if (!isset($this->tokens[$this->position])) {
            throw new ExpressionSyntaxError('Unexpected end of expression', $this->current->cursor);
        }

        --$this->position;

        $this->current = $this->tokens[$this->position];
    }
}
