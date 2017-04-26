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
            if (strpos($value, MatchApplicableChecker::OPERATOR_AND)) {
                $operator = MatchApplicableChecker::OPERATOR_AND;
                $value = explode(MatchApplicableChecker::OPERATOR_AND, $value);
            } elseif (strpos($value, MatchApplicableChecker::OPERATOR_OR)) {
                $operator = MatchApplicableChecker::OPERATOR_OR;
                $value = explode(MatchApplicableChecker::OPERATOR_OR, $value);
            } elseif (0 === strpos($value, MatchApplicableChecker::OPERATOR_NOT)) {
                $value = [MatchApplicableChecker::OPERATOR_NOT => substr($value, 1)];
            }
            if (null !== $operator) {
                return [
                    $operator => array_map(
                        function ($val) {
                            return 0 === strpos($val, MatchApplicableChecker::OPERATOR_NOT)
                                ? [MatchApplicableChecker::OPERATOR_NOT => substr($val, 1)]
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
