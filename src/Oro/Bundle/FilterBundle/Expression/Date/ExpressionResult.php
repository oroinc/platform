<?php

namespace Oro\Bundle\FilterBundle\Expression\Date;

use Carbon\Carbon;
use Oro\Bundle\FilterBundle\Expression\Exception\ExpressionDenied;
use Oro\Bundle\FilterBundle\Expression\Exception\SyntaxException;
use Oro\Bundle\FilterBundle\Provider\DateModifierInterface;

/**
 * A wrapper for date, time or datetime value that can represent expressions
 * like "today", "start of week", "current year", etc.
 *
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class ExpressionResult
{
    const TYPE_INT = 1;
    const TYPE_DATE = 2;
    const TYPE_TIME = 3;
    const TYPE_DAYMONTH = 4;
    const TYPE_DATETIME = 5;

    /** @var int */
    private $variableType = null;

    private $variableLabel = null;

    /** @var int */
    private $sourceType;

    /** @var mixed */
    private $value;

    /**
     * @param Token|int $value
     * @param string|null $timezone
     */
    public function __construct(Token|int $value, ?string $timezone = null)
    {
        $timezone = $timezone ?: 'UTC';
        if ($value instanceof Token) {
            switch ($value->getType()) {
                case Token::TYPE_VARIABLE:
                    $this->processTypeVariable($value, $timezone);
                    break;
                case Token::TYPE_TIME:
                    $this->processTypeTime($value);
                    break;
                case Token::TYPE_DATE:
                    $this->processTypeDate($value);
                    break;
                case Token::TYPE_INTEGER:
                    $this->processTypeInteger($value);
                    break;
                case Token::TYPE_DAYMONTH:
                    $this->processTypeDayMonth($value, $timezone);
                    break;
            }
        } else {
            $this->processTypeInteger($value);
        }
    }

    private function processTypeVariable(Token $token, string $timezone): void
    {
        $dateValue = Carbon::now($timezone);

        switch ($token->getValue()) {
            case DateModifierInterface::VAR_THIS_DAY:
            case DateModifierInterface::VAR_TODAY:
            case DateModifierInterface::VAR_THIS_DAY_W_Y:
                $dateValue->startOfDay();
                break;
            case DateModifierInterface::VAR_SOW:
            case DateModifierInterface::VAR_THIS_WEEK:
                //do not use start of the week due to it always use monday as 1 day
                $dateValue->modify('this week');
                $dateValue->startOfDay();
                break;
            case DateModifierInterface::VAR_SOM:
            case DateModifierInterface::VAR_THIS_MONTH:
            case DateModifierInterface::VAR_THIS_MONTH_W_Y:
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
        $this->variableType = (int)$token->getValue();
        $this->variableLabel = (string)$token;
        $this->sourceType = self::TYPE_DATE;
    }

    private function processTypeTime(Token $token): void
    {
        $dateValue = Carbon::parse('now');
        call_user_func_array([$dateValue, 'setTime'], explode(':', $token->getValue()));

        $this->value = $dateValue;
        $this->sourceType = self::TYPE_TIME;
    }

    private function processTypeDate(Token $token): void
    {
        $this->sourceType = self::TYPE_DATE;
        $this->value = Carbon::parse($token->getValue());
    }

    /**
     * @param Token|int $token
     */
    private function processTypeInteger(Token|int $token): void
    {
        $this->sourceType = self::TYPE_INT;
        if (is_numeric($token)) {
            $this->value = $token;
        } else {
            $this->value = $token->getValue();
        }
    }

    private function processTypeDayMonth(Token $token, string $timeZone): void
    {
        $this->sourceType = self::TYPE_DAYMONTH;
        //don't worry about date(Y), later we get only day and month
        $this->value = Carbon::parse(date('Y') . '-' . $token->getValue(), $timeZone);
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
     * @return null|string
     */
    public function getVariableLabel()
    {
        return $this->variableLabel;
    }

    /**
     * @return int
     */
    public function getSourceType()
    {
        return $this->sourceType;
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
            $dateValue = $this->getValue();
            switch ($this->getVariableType()) {
                case DateModifierInterface::VAR_NOW:
                case DateModifierInterface::VAR_TODAY:
                case DateModifierInterface::VAR_THIS_DAY:
                case DateModifierInterface::VAR_THIS_DAY_W_Y:
                case DateModifierInterface::VAR_SOW:
                case DateModifierInterface::VAR_SOM:
                case DateModifierInterface::VAR_SOQ:
                case DateModifierInterface::VAR_SOY:
                case DateModifierInterface::VAR_FDQ:
                    $dateValue->addDays($value->getValue());
                    break;
                case DateModifierInterface::VAR_THIS_WEEK:
                    $dateValue->addWeeks($value->getValue());
                    break;
                case DateModifierInterface::VAR_FMQ:
                case DateModifierInterface::VAR_THIS_MONTH:
                case DateModifierInterface::VAR_THIS_MONTH_W_Y:
                    $dateValue->addMonths($value->getValue());
                    break;
                case DateModifierInterface::VAR_THIS_QUARTER:
                    $dateValue->month(($dateValue->quarter + $value->getValue()) * 3);
                    break;
                case DateModifierInterface::VAR_THIS_YEAR:
                    $dateValue->addYears($value->getValue());
                    break;
                default:
                    throw new ExpressionDenied($this->getVariableLabel());
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
                case DateModifierInterface::VAR_THIS_DAY_W_Y:
                case DateModifierInterface::VAR_SOW:
                case DateModifierInterface::VAR_SOM:
                case DateModifierInterface::VAR_SOQ:
                case DateModifierInterface::VAR_SOY:
                case DateModifierInterface::VAR_FDQ:
                    $dateValue->subDays($value->getValue());
                    break;
                case DateModifierInterface::VAR_THIS_WEEK:
                    $dateValue->subWeeks($value->getValue());
                    break;
                case DateModifierInterface::VAR_FMQ:
                case DateModifierInterface::VAR_THIS_MONTH:
                case DateModifierInterface::VAR_THIS_MONTH_W_Y:
                    $dateValue->subMonths($value->getValue());
                    break;
                case DateModifierInterface::VAR_THIS_QUARTER:
                    $dateValue->month(($dateValue->quarter - $value->getValue()) * 3);
                    break;
                case DateModifierInterface::VAR_THIS_YEAR:
                    $dateValue->subYears($value->getValue());
                    break;
                default:
                    throw new ExpressionDenied($this->getVariableLabel());
                    break;
            }
        } elseif (!$value->isModifier()) {
            switch ($value->getVariableType()) {
                case DateModifierInterface::VAR_NOW:
                case DateModifierInterface::VAR_TODAY:
                case DateModifierInterface::VAR_THIS_DAY:
                case DateModifierInterface::VAR_SOW:
                case DateModifierInterface::VAR_SOM:
                case DateModifierInterface::VAR_SOQ:
                case DateModifierInterface::VAR_SOY:
                case DateModifierInterface::VAR_FDQ:
                    $this->value -= $value->getValue()->day;
                    break;
                case DateModifierInterface::VAR_THIS_WEEK:
                    $this->value -= $value->getValue()->format('W');
                    break;
                case DateModifierInterface::VAR_FMQ:
                case DateModifierInterface::VAR_THIS_MONTH:
                case DateModifierInterface::VAR_THIS_MONTH_W_Y:
                    $this->value -= $value->getValue()->month;
                    break;
                case DateModifierInterface::VAR_THIS_QUARTER:
                    $this->value -= $value->getValue()->quarter;
                    break;
                case DateModifierInterface::VAR_THIS_YEAR:
                    $this->value -= $value->getValue()->year;
                    break;
                default:
                    throw new ExpressionDenied($value->getVariableLabel());
                    break;
            }
        } else {
            $this->value -= $value->getValue();
        }

        return $this;
    }

    /**
     * Merges two results by rules
     *
     * @throws SyntaxException
     */
    public function merge(ExpressionResult $expression)
    {
        if (self::TYPE_DATE === $this->getSourceType()) {
            /** @var Carbon $dateValue */
            $dateValue = $this->getValue();
            $dateValue->second($expression->getValue()->second);
            $dateValue->minute($expression->getValue()->minute);
            $dateValue->hour($expression->getValue()->hour);
            $this->sourceType = self::TYPE_DATETIME;
        } elseif (self::TYPE_TIME === $this->getSourceType()) {
            $expression->merge($this);
            $this->value = $expression->getValue();
            $this->sourceType = self::TYPE_DATETIME;
        } else {
            throw new ExpressionDenied($expression->getVariableLabel());
        }
    }
}
