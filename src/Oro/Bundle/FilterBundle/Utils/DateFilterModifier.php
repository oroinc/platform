<?php
namespace Oro\Bundle\FilterBundle\Utils;

use Symfony\Component\Validator\Exception\UnexpectedTypeException;

use Oro\Bundle\FilterBundle\Expression\Date\Compiler;
use Oro\Bundle\FilterBundle\Expression\Date\ExpressionResult;
use Oro\Bundle\FilterBundle\Form\Type\Filter\AbstractDateFilterType;
use Oro\Bundle\FilterBundle\Provider\DateModifierInterface;

/**
 * Class DateFilterModifier
 * @package Oro\Bundle\FilterBundle\Utils
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
        $data = $this->modifyDateForEqualType($data);
        $data = $this->modifyPartByVariable($data);
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
     * Modify filter when selected (source or value) and (equals or not equals) and today, start_of_* modifiers
     * For example: equals today convert to between from 2015-11-25 00:00:00 to 2015-11-25 23:59:59
     * It's normal user's expectations
     *
     * @param array $data
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     *
     * @return array
     */
    protected function modifyDateForEqualType(array $data)
    {
        if (isset($data['part'], $data['type'])) {
            $validType =
                $data['type'] == AbstractDateFilterType::TYPE_EQUAL ||
                $data['type'] == AbstractDateFilterType::TYPE_NOT_EQUAL;
            $validPart =
                $data['part'] === DateModifierInterface::PART_SOURCE ||
                $data['part'] === DateModifierInterface::PART_VALUE;

            if (isset($data['value']) && $validType && $validPart) {
                if ($data['type'] == AbstractDateFilterType::TYPE_EQUAL) {
                    $date = $data['value']['start'];
                } else {
                    $date = $data['value']['end'];
                }
                $result = $this->dateCompiler->compile($date, true);

                if ($result instanceof ExpressionResult) {
                    switch ($result->getVariableType()) {
                        case DateModifierInterface::VAR_TODAY:
                        case DateModifierInterface::VAR_SOW:
                        case DateModifierInterface::VAR_SOM:
                        case DateModifierInterface::VAR_SOQ:
                        case DateModifierInterface::VAR_SOY:
                            /** @var \Carbon\Carbon $date */
                            $date       = $this->dateCompiler->compile($date);
                            $clonedDate = clone $date;
                            if ($data['type'] == AbstractDateFilterType::TYPE_EQUAL) {
                                $data['value']['end'] = $clonedDate->endOfDay()->format('Y-m-d H:i');
                                $data['type']         = AbstractDateFilterType::TYPE_BETWEEN;
                            } else {
                                $data['type']           = AbstractDateFilterType::TYPE_NOT_BETWEEN;
                                $data['value']['start'] = $data['value']['end'];
                                $data['value']['end']   = $clonedDate->endOfDay()->format('Y-m-d H:i');
                            }
                            break;
                    }
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
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @param array $data
     *
     * @return array
     */
    protected function modifyPartByVariable(array $data)
    {
        if (isset($data['part'], $data['type'])) {
            foreach ($data['value'] as $field) {
                if ($field) {
                    if ($field instanceof \DateTime) {
                        continue;
                    }
                    $result = $this->dateCompiler->compile($field, true);

                    switch ($result->getVariableType()) {
                        case DateModifierInterface::VAR_THIS_DAY_W_Y:
                            $data['part'] = DateModifierInterface::PART_VALUE;
                            break;
                        case DateModifierInterface::VAR_THIS_MONTH:
                            $data['part'] = DateModifierInterface::PART_MONTH;
                            break;
                    }

                    switch ($result->getSourceType()) {
                        case ExpressionResult::TYPE_DAYMONTH:
                            $data['part'] = DateModifierInterface::PART_VALUE;
                            break;
                    }
                }
            }
        }

        return $data;
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
            if (isset($data['value'], $data['value'][$key])) {
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
                    return (int)$value->format($part);
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
