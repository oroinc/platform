<?php

namespace Oro\Bundle\QueryDesignerBundle\QueryDesigner;

use Oro\Bundle\QueryDesignerBundle\Exception\InvalidFiltersException;

class FiltersParserContext
{
    const NONE_TOKEN        = 0;
    const OPERATOR_TOKEN    = 1;
    const FILTER_TOKEN      = 2;
    const BEGIN_GROUP_TOKEN = 3;
    const END_GROUP_TOKEN   = 4;

    /**
     * @var int
     */
    protected $lastTokenType = self::NONE_TOKEN;

    /**
     * @var mixed
     */
    protected $lastToken = null;

    /**
     * @var int
     */
    protected $lastTokenIndex = -1;

    /**
     * Gets a type of the last token
     *
     * @return int
     */
    public function getLastTokenType()
    {
        return $this->lastTokenType;
    }

    /**
     * Sets a type of the last token
     */
    public function setLastTokenType($lastTokenType)
    {
        $this->lastTokenType = $lastTokenType;
    }

    /**
     * Gets the last token
     *
     * @return int
     */
    public function getLastToken()
    {
        return $this->lastToken;
    }

    /**
     * Sets the last token
     */
    public function setLastToken($lastToken)
    {
        $this->lastToken = $lastToken;
        $this->lastTokenIndex++;
    }

    /**
     * Checks if a new group can be started
     */
    public function checkBeginGroup()
    {
        if ($this->lastTokenType !== self::NONE_TOKEN &&
            $this->lastTokenType !== self::OPERATOR_TOKEN &&
            $this->lastTokenType !== self::BEGIN_GROUP_TOKEN
        ) {
            $this->throwInvalidFiltersException('unexpected begin of group');
        }
    }

    /**
     * Checks if a current group can be closed
     */
    public function checkEndGroup()
    {
        if ($this->lastTokenType !== self::FILTER_TOKEN &&
            $this->lastTokenType !== self::END_GROUP_TOKEN
        ) {
            $this->throwInvalidFiltersException('unexpected end of group');
        }
    }

    /**
     * Checks if the next token can be the given operator
     */
    public function checkOperator($operator)
    {
        if ($this->lastTokenType !== self::FILTER_TOKEN &&
            $this->lastTokenType !== self::END_GROUP_TOKEN
        ) {
            $this->throwInvalidFiltersException(
                sprintf('unexpected "%s" operator', $this->convertTokenToString($operator))
            );
        }
    }

    /**
     * Checks if the next token can be the given filter
     */
    public function checkFilter($filter)
    {
        if ($this->lastTokenType !== self::NONE_TOKEN &&
            $this->lastTokenType !== self::OPERATOR_TOKEN &&
            $this->lastTokenType !== self::BEGIN_GROUP_TOKEN
        ) {
            $this->throwInvalidFiltersException(
                sprintf('a filter is unexpected here. Filter: "%s"', $this->convertTokenToString($filter))
            );
        }
    }

    /**
     * Raises InvalidFiltersException
     *
     * @param string $msg
     * @throws InvalidFiltersException
     */
    public function throwInvalidFiltersException($msg)
    {
        throw new InvalidFiltersException(
            sprintf(
                'Invalid filters structure; %s. Nearest token: %s. Nearest token number: %d.',
                $msg,
                $this->convertTokenToString($this->lastToken),
                $this->lastTokenIndex + 1
            )
        );
    }

    /**
     * Returns a human readable representation of the given token
     *
     * @param mixed $token
     * @return string
     */
    public function convertTokenToString($token)
    {
        if (is_string($token)) {
            return $token;
        }

        return print_r($token, true);
    }
}
