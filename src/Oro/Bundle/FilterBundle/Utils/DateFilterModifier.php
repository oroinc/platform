<?php

namespace Oro\Bundle\FilterBundle\Utils;

use Oro\Bundle\FilterBundle\Expression\Date\Compiler;
use Oro\Bundle\FilterBundle\Expression\Date\ExpressionResult;
use Oro\Bundle\FilterBundle\Form\Type\Filter\AbstractDateFilterType;
use Oro\Bundle\FilterBundle\Provider\DateModifierInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Provides a method to modify a data for date, time or datetime filters before this data is passed to the filters.
 * @see \Oro\Bundle\FilterBundle\Filter\AbstractDateFilter
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class DateFilterModifier
{
    /** @var Compiler */
    protected $dateCompiler;

    /** @var array */
    protected static $partFormatsMap = [
        DateModifierInterface::PART_MONTH => 'm',
        DateModifierInterface::PART_DOW   => 'N',
        DateModifierInterface::PART_WEEK  => 'W',
        DateModifierInterface::PART_DAY   => 'd',
        DateModifierInterface::PART_DOY   => 'z',
        DateModifierInterface::PART_YEAR  => 'Y',
    ];

    /**
     * @param Compiler $compiler
     */
    public function __construct(Compiler $compiler)
    {
        $this->dateCompiler = $compiler;
    }

    /**
     * Parses and modifies date filter data accordingly to part and value types
     *
     * @param array $data
     * @param array $valueKeys
     * @param bool  $compile
     *
     * @return array
     */
    public function modify(array $data, array $valueKeys = ['start', 'end'], $compile = true)
    {
        if (isset($data['value'], $data['type'])) {
            if ($this->isEqualType($data)) {
                $data = $this->modifyDateForEqualType($data);
            } elseif ($this->isBetweenType($data)) {
                $data = $this->modifyDateForBetweenType($data);
            }
            $data = $this->modifyPartByVariable($data);
        }

        // compile expressions
        if ($compile) {
            $data = $this->mapValues($valueKeys, $data, $this->getCompileClosure());
        }

        $data['part'] = isset($data['part']) ? $data['part'] : null;

        // change value type depending on date part
        if (array_key_exists($data['part'], static::$partFormatsMap)) {
            $format = static::$partFormatsMap[$data['part']];
            $data   = $this->mapValues($valueKeys, $data, $this->getDatePartAccessorClosure($format));
        } elseif ($data['part'] === DateModifierInterface::PART_QUARTER) {
            $data = $this->mapValues($valueKeys, $data, $this->getQuarterMapValuesClosure());
        } elseif ($compile) {
            $data = $this->mapValues($valueKeys, $data, $this->getValueMapValuesClosure());
        }

        return $data;
    }

    /**
     * @param array $data
     *
     * @return bool
     */
    protected function isEqualType(array $data)
    {
        return
            (
                AbstractDateFilterType::TYPE_EQUAL == $data['type']
                || AbstractDateFilterType::TYPE_NOT_EQUAL == $data['type']
            )
            && (
                !isset($data['part'])
                || DateModifierInterface::PART_SOURCE === $data['part']
                || DateModifierInterface::PART_VALUE === $data['part']
            );
    }

    /**
     * Modify filter when selected (source or value) and (equals or not equals) and today, start_of_* modifiers
     * For example "equals today" is converted to "between 2015-11-25 00:00:00 to 2015-11-26 00:00:00"
     * It's normal user's expectations
     *
     * @param array $data
     *
     * @return array
     */
    protected function modifyDateForEqualType(array $data)
    {
        $isEqualType = AbstractDateFilterType::TYPE_EQUAL == $data['type'];
        $date = $isEqualType
            ? $data['value']['start']
            : $data['value']['end'];
        if ($date && !$date instanceof \DateTime) {
            $result = $this->dateCompiler->compile($date, true);
            if ($result instanceof ExpressionResult) {
                switch ($result->getVariableType()) {
                    case DateModifierInterface::VAR_TODAY:
                    case DateModifierInterface::VAR_SOW:
                    case DateModifierInterface::VAR_SOM:
                    case DateModifierInterface::VAR_SOQ:
                    case DateModifierInterface::VAR_SOY:
                        $data['type'] = $this->convertEqualToBetweenType($isEqualType);
                        if ($isEqualType) {
                            $data['value']['end'] = $this->modifyDate($result->getValue(), '1 day');
                        } else {
                            $data['value']['start'] = $data['value']['end'];
                            $data['value']['end'] = $this->modifyDate($result->getValue(), '1 day');
                        }
                        break;
                    case null:
                        $data['type'] = $this->convertEqualToBetweenType($isEqualType);
                        if ($data['type'] === AbstractDateFilterType::TYPE_NOT_BETWEEN) {
                            $data['value']['start'] = $data['value']['end'];
                        }
                        if (ExpressionResult::TYPE_DATE === $result->getSourceType()) {
                            $data['value']['end'] = $this->modifyDate($result->getValue(), '1 day');
                        } else {
                            $data['value']['end'] = $this->modifyDate($result->getValue(), '1 minute');
                        }
                        break;
                }
            }
        }

        return $data;
    }

    /**
     * @param bool $isEqualType
     *
     * @return int
     */
    protected function convertEqualToBetweenType($isEqualType)
    {
        return $isEqualType
            ? AbstractDateFilterType::TYPE_BETWEEN
            : AbstractDateFilterType::TYPE_NOT_BETWEEN;
    }

    /**
     * @param array $data
     *
     * @return bool
     */
    protected function isBetweenType(array $data)
    {
        return
            (
                AbstractDateFilterType::TYPE_BETWEEN == $data['type']
                || AbstractDateFilterType::TYPE_NOT_BETWEEN == $data['type']
            )
            && (
                !isset($data['part'])
                || DateModifierInterface::PART_SOURCE === $data['part']
                || DateModifierInterface::PART_VALUE === $data['part']
            );
    }

    /**
     * Modify filter when selected (source or value) and (between or not between) and today, start_of_* modifiers
     * For example "between today and today" is converted to
     * "between 2015-11-25 00:00:00 and 2015-11-26 00:00:00".
     *
     * @param array $data
     *
     * @return array
     */
    protected function modifyDateForBetweenType(array $data)
    {
        $endDate = $data['value']['end'];
        if ($endDate && !$endDate instanceof \DateTime) {
            $result = $this->dateCompiler->compile($endDate, true);
            if ($result instanceof ExpressionResult) {
                switch ($result->getVariableType()) {
                    case DateModifierInterface::VAR_TODAY:
                    case DateModifierInterface::VAR_SOW:
                    case DateModifierInterface::VAR_SOM:
                    case DateModifierInterface::VAR_SOQ:
                    case DateModifierInterface::VAR_SOY:
                        $data['value']['end'] = $this->modifyDate($result->getValue(), '1 day');
                        break;
                    case null:
                        if (ExpressionResult::TYPE_DATE === $result->getSourceType()) {
                            $data['value']['end'] = $this->modifyDate($result->getValue(), '1 day');
                        } else {
                            $data['value']['end'] = $this->modifyDate($result->getValue(), '1 minute');
                        }
                        break;
                }
            }
        }

        return $data;
    }

    /**
     * Doesn't matter which part was selected. This variables should contain own certain part.
     * To support this approach see that now grid doesn't contain 'part' select box and backend must
     * change 'part' dynamically
     *
     * @param array $data
     *
     * @return array
     */
    protected function modifyPartByVariable(array $data)
    {
        if (isset($data['part'])) {
            foreach ($data['value'] as $field) {
                if ($field && !$field instanceof \DateTime) {
                    $result = $this->dateCompiler->compile($field, true);

                    switch ($result->getVariableType()) {
                        case DateModifierInterface::VAR_THIS_DAY_W_Y:
                            $data['part'] = DateModifierInterface::PART_VALUE;
                            break;
                        case DateModifierInterface::VAR_THIS_MONTH:
                            $data['part'] = DateModifierInterface::PART_MONTH;
                            break;
                    }

                    if (ExpressionResult::TYPE_DAYMONTH === $result->getSourceType()) {
                        $data['part'] = DateModifierInterface::PART_VALUE;
                    }
                }
            }
        }

        return $data;
    }

    /**
     * @param \DateTime $date
     * @param string    $modify
     *
     * @return string
     */
    protected function modifyDate(\DateTime $date, $modify)
    {
        return (clone $date)->modify($modify)->format('Y-m-d H:i');
    }

    /**
     * Call callback for each of given value, used instead of array_map to walk safely through array
     *
     * @param array    $keys
     * @param array    $data
     * @param \Closure $callback
     *
     * @return array
     */
    protected function mapValues(array $keys, array $data, \Closure $callback)
    {
        foreach ($keys as $key) {
            if (isset($data['value'][$key])) {
                $data['value'][$key] = $callback($data['value'][$key]);
            }
        }

        return $data;
    }

    /**
     * Returns callable that able to retrieve needed datePart from compiler result
     *
     * @param string $part
     *
     * @return \Closure
     */
    private function getDatePartAccessorClosure($part)
    {
        return function ($value) use ($part) {
            switch (true) {
                case is_numeric($value):
                    return (int)$value;
                    break;
                case ($value instanceof \DateTime):
                    $result = (int)$value->format($part);
                    // In case if the Day Of Year was triggered, the value should be incremented because 'z' format
                    // returns values from 0 to 365.
                    // @see http://php.net/manual/en/function.date.php
                    if ('z' === $part) {
                        $result ++;
                    }
                    return $result;
                    break;
                default:
                    throw new UnexpectedTypeException($value, 'integer or \DateTime');
            }
        };
    }

    /**
     * @return \Closure
     */
    protected function getCompileClosure()
    {
        return function ($data) {
            return $this->dateCompiler->compile($data);
        };
    }

    /**
     * @return \Closure
     */
    protected function getQuarterMapValuesClosure()
    {
        return function ($data) {
            $quarter = null;
            switch (true) {
                case is_numeric($data):
                    $quarter = (int)$data;
                    break;
                case ($data instanceof \DateTime):
                    $month   = (int)$data->format('m');
                    $quarter = ceil($month / 3);
                    break;
                default:
                    throw new UnexpectedTypeException($data, 'integer or \DateTime');
            }

            return $quarter;
        };
    }

    /**
     * @return \Closure
     */
    protected function getValueMapValuesClosure()
    {
        return function ($data) {
            // html5 format for intl
            return $data instanceof \DateTime ? $data->format('Y-m-d H:i') :
                (is_numeric($data) ? sprintf('2015-%\'.02d-01 00:00', $data) : $data);
        };
    }
}
