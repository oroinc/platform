<?php

namespace Oro\Component\ChainProcessor;

/**
 * Provides a static method that can be used to convert a string representation of an expression
 * to an array that is ready to be used in matchers.
 * @see \Oro\Component\ChainProcessor\AbstractMatcher
 * @see \Oro\Component\ChainProcessor\MatchApplicableChecker
 */
class ExpressionParser
{
    private const EXISTS_VALUE = 'exists';

    /**
     * Checks if the given expression is a string and if so, parses it and returns an array
     * contains the parsed expression that is ready to be used in matchers.
     *
     * @param mixed $value
     *
     * @return mixed
     */
    public static function parse($value)
    {
        if (\is_string($value)) {
            $operator = null;
            if (strpos($value, AbstractMatcher::OPERATOR_AND)) {
                $operator = AbstractMatcher::OPERATOR_AND;
                $value = explode(AbstractMatcher::OPERATOR_AND, $value);
            } elseif (strpos($value, AbstractMatcher::OPERATOR_OR)) {
                $operator = AbstractMatcher::OPERATOR_OR;
                $value = explode(AbstractMatcher::OPERATOR_OR, $value);
            } else {
                $value = self::normalizeValue($value);
            }
            if (null !== $operator) {
                $items = [];
                foreach ($value as $val) {
                    if (str_starts_with($val, AbstractMatcher::OPERATOR_NOT)) {
                        $val = substr($val, 1);
                        if (self::EXISTS_VALUE === $val) {
                            throw new \InvalidArgumentException(sprintf(
                                'The operator "!%s" cannot be used together with "%s" operator.',
                                self::EXISTS_VALUE,
                                $operator
                            ));
                        }
                        $val = [AbstractMatcher::OPERATOR_NOT => $val];
                    } elseif (self::EXISTS_VALUE === $val) {
                        throw new \InvalidArgumentException(sprintf(
                            'The operator "%s" cannot be used together with "%s" operator.',
                            self::EXISTS_VALUE,
                            $operator
                        ));
                    }
                    $items[] = $val;
                }

                return [$operator => $items];
            }
        }

        return $value;
    }

    /**
     * @param string $value
     *
     * @return mixed
     */
    private static function normalizeValue(string $value)
    {
        if (str_starts_with($value, AbstractMatcher::OPERATOR_NOT)) {
            $value = substr($value, 1);
            if (self::EXISTS_VALUE === $value) {
                return null;
            }

            return [AbstractMatcher::OPERATOR_NOT => $value];
        }

        if (self::EXISTS_VALUE === $value) {
            return [AbstractMatcher::OPERATOR_NOT => null];
        }

        return $value;
    }
}
