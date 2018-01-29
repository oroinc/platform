<?php

namespace Oro\Component\ChainProcessor;

class ExpressionParser
{
    /**
     * @param mixed $value
     *
     * @return mixed
     */
    public static function parse($value)
    {
        if (is_string($value)) {
            $operator = null;
            if (strpos($value, AbstractMatcher::OPERATOR_AND)) {
                $operator = AbstractMatcher::OPERATOR_AND;
                $value = explode(AbstractMatcher::OPERATOR_AND, $value);
            } elseif (strpos($value, AbstractMatcher::OPERATOR_OR)) {
                $operator = AbstractMatcher::OPERATOR_OR;
                $value = explode(AbstractMatcher::OPERATOR_OR, $value);
            } elseif (0 === strpos($value, AbstractMatcher::OPERATOR_NOT)) {
                $value = [AbstractMatcher::OPERATOR_NOT => substr($value, 1)];
            }
            if (null !== $operator) {
                return [
                    $operator => array_map(
                        function ($val) {
                            return 0 === strpos($val, AbstractMatcher::OPERATOR_NOT)
                                ? [AbstractMatcher::OPERATOR_NOT => substr($val, 1)]
                                : $val;
                        },
                        $value
                    )
                ];
            }
        }

        return $value;
    }
}
