<?php

namespace Oro\Bundle\FilterBundle\Expression\Date;

use Carbon\Carbon;

use Oro\Bundle\FilterBundle\Expression\Exception\SyntaxException;
use Oro\Bundle\FilterBundle\Provider\DateModifierInterface;

class ExpressionResult
{
    const TYPE_INT  = 1;
    const TYPE_DATE = 2;
    const TYPE_TIME = 3;

    /** @var int */
    private $variableType = null;

    /** @var int */
    private $sourceType;

    /** @var mixed */
    private $value;

    public function __construct($value)
    {
        if (is_numeric($value)) {
            $this->value      = $value;
            $this->sourceType = self::TYPE_INT;
        } elseif ($value instanceof Token && $value->is(Token::TYPE_VARIABLE)) {
            $dateValue = Carbon::now(new \DateTimeZone('UTC'));

            switch ($value->getValue()) {
                case DateModifierInterface::VAR_SOW:
                case DateModifierInterface::VAR_THIS_WEEK:
                    //do not use start of the week due to it always use monday as 1 day
                    $dateValue->modify('this week');
                    $dateValue->startOfDay();
                    break;
                case DateModifierInterface::VAR_SOM:
                case DateModifierInterface::VAR_THIS_MONTH:
                    $dateValue->firstOfMonth();
                    break;
                case DateModifierInterface::VAR_FMQ:
                case DateModifierInterface::VAR_SOQ:
                case DateModifierInterface::VAR_THIS_QUARTER:
                case DateModifierInterface::VAR_FDQ:
                    $dateValue->firstOfQuarter();
                    break;
                case DateModifierInterface::VAR_SOY:
                case DateModifierInterface::VAR_THIS_YEAR:
                    $dateValue->firstOfYear();
                    break;
            }

            $this->value = $dateValue;

            $this->variableType = $value->getValue();
            $this->sourceType   = self::TYPE_DATE;
        } elseif ($value instanceof Token && $value->is(Token::TYPE_TIME)) {
            $dateValue = Carbon::parse('now', new \DateTimeZone('UTC'));
            call_user_func_array([$dateValue, 'setTime'], explode(':', $value->getValue()));

            $this->value      = $dateValue;
            $this->sourceType = self::TYPE_TIME;
        } elseif ($value instanceof Token && $value->is(Token::TYPE_DATE)) {
            $this->sourceType = self::TYPE_DATE;
            $this->value      = Carbon::parse($value->getValue(), new \DateTimeZone('UTC'));
        } elseif ($value instanceof Token && $value->is(Token::TYPE_INTEGER)) {
            $this->sourceType = self::TYPE_INT;
            $this->value      = $value->getValue();
        }
    }

    /**
     * @return bool
     */
    public function isModifier()
    {
        return $this->sourceType === self::TYPE_INT;
    }

    /**
     * @return int
     */
    public function getVariableType()
    {
        return $this->variableType;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param ExpressionResult $value
     *
     * @throws SyntaxException
     * @return ExpressionResult
     */
    public function add(ExpressionResult $value)
    {
        if (!$this->isModifier()) {
            /** @var Carbon $dateValue */
            $dateValue = $this->value;
            switch ($this->variableType) {
                case DateModifierInterface::VAR_NOW:
                case DateModifierInterface::VAR_TODAY:
                case DateModifierInterface::VAR_THIS_DAY:
                    $dateValue->addDays($value->getValue());
                    break;
                case DateModifierInterface::VAR_THIS_WEEK:
                    $dateValue->addWeeks($value->getValue());
                    break;
                case DateModifierInterface::VAR_FMQ:
                case DateModifierInterface::VAR_THIS_MONTH:
                    $dateValue->addMonths($value->getValue());
                    break;
                case DateModifierInterface::VAR_THIS_QUARTER:
                    $dateValue->month(($dateValue->quarter + $value->getValue()) * 3);
                    break;
                case DateModifierInterface::VAR_THIS_YEAR:
                    $dateValue->addYears($value->getValue());
                    break;
                default:
                    throw new SyntaxException('Expressions with this variable is not allowed');
                    break;
            }
        } elseif (!$value->isModifier()) {
            $value->add($this);

            $this->value = $value->getValue();
        } else {
            $this->value += $value->getValue();
        }

        return $this;
    }

    /**
     * @param ExpressionResult $value
     *
     * @throws SyntaxException
     * @return ExpressionResult
     */
    public function subtract(ExpressionResult $value)
    {
        if (!$this->isModifier()) {
            /** @var Carbon $dateValue */
            $dateValue = $this->getValue();
            switch ($this->getVariableType()) {
                case DateModifierInterface::VAR_NOW:
                case DateModifierInterface::VAR_TODAY:
                case DateModifierInterface::VAR_THIS_DAY:
                    $dateValue->subDays($value->getValue());
                    break;
                case DateModifierInterface::VAR_THIS_WEEK:
                    $dateValue->subWeeks($value->getValue());
                    break;
                case DateModifierInterface::VAR_FMQ:
                case DateModifierInterface::VAR_THIS_MONTH:
                    $dateValue->subMonths($value->getValue());
                    break;
                case DateModifierInterface::VAR_THIS_QUARTER:
                    $dateValue->month(($dateValue->quarter - $value->getValue()) * 3);
                    break;
                case DateModifierInterface::VAR_THIS_YEAR:
                    $dateValue->subYears($value->getValue());
                    break;
                default:
                    throw new SyntaxException('Expressions with this variable is not allowed');
                    break;
            }
        } elseif (!$value->isModifier()) {
            switch ($value->getVariableType()) {
                case DateModifierInterface::VAR_NOW:
                case DateModifierInterface::VAR_TODAY:
                case DateModifierInterface::VAR_THIS_DAY:
                    $this->value -= $value->getValue()->day;
                    break;
                case DateModifierInterface::VAR_THIS_WEEK:
                    $this->value -= $value->getValue()->format('W');
                    break;
                case DateModifierInterface::VAR_FMQ:
                case DateModifierInterface::VAR_THIS_MONTH:
                    $this->value -= $value->getValue()->month;
                    break;
                case DateModifierInterface::VAR_THIS_QUARTER:
                    $this->value -= $value->getValue()->quarter;
                    break;
                case DateModifierInterface::VAR_THIS_YEAR:
                    $this->value -= $value->getValue()->year;
                    break;
                default:
                    throw new SyntaxException('Expressions with this variable is not allowed');
                    break;
            }
        } else {
            $this->value -= $value->getValue();
        }

        return $this;
    }
}
