<?php

namespace Oro\Bundle\FilterBundle\Filter;

use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\FilterBundle\Provider\DateModifierInterface;
use Oro\Bundle\FilterBundle\Form\Type\Filter\DateRangeFilterType;
use Oro\Bundle\FilterBundle\Expression\Date\Compiler;
use Oro\Bundle\FilterBundle\Expression\Date\ExpressionResult;

class DateFilterUtility
{
    /** @var LocaleSettings */
    protected $localeSettings;

    /** @var string */
    private $offset;

    /** @var Compiler */
    protected $expressionCompiler;

    /**
     * @param LocaleSettings $localeSettings
     * @param Compiler $compiler
     */
    public function __construct(LocaleSettings $localeSettings, Compiler $compiler)
    {
        $this->localeSettings = $localeSettings;
        $this->expressionCompiler = $compiler;
    }

    /**
     * Parses validates and prepare data for date/datetime filters
     *
     * @param string $field
     * @param mixed  $data
     *
     * @return array|bool
     */
    public function parseData($field, $data)
    {
        if (!$this->isValidData($data)) {
            return false;
        }

        $data['value'] = array_merge(['start' => null, 'end' => null], $data['value']);
        $data['type']  = isset($data['type']) ? $data['type'] : DateRangeFilterType::TYPE_BETWEEN;

        // values will not be used, so just unset them
        if ($data['type'] == DateRangeFilterType::TYPE_MORE_THAN) {
            $data['value']['end'] = null;
            $data['value']['end_original'] = null;
        } elseif ($data['type'] == DateRangeFilterType::TYPE_LESS_THAN) {
            $data['value']['start'] = null;
            $data['value']['start_original'] = null;
        }

        $data = [
            'date_start'          => $data['value']['start'],
            'date_end'            => $data['value']['end'],
            'date_start_original' => $data['value']['start_original'],
            'date_end_original'   => $data['value']['end_original'],
            'type'                => $data['type'],
            'part'                => isset($data['part']) ? $data['part'] : DateModifierInterface::PART_VALUE,
            'field'               => $field
        ];
        $data = $this->applyDatePart($data);

        return $data;
    }

    /**
     * Validates filter data
     *
     * @param mixed $data
     *
     * @return bool
     */
    protected function isValidData($data)
    {
        if (!is_array($data) || !array_key_exists('value', $data) || !is_array($data['value'])) {
            return false;
        }

        if (!isset($data['value']['start']) && !isset($data['value']['end'])) {
            return false;
        }

        return true;
    }

    /**
     * Applies datepart expressions
     *
     * @param array $data
     *
     * @return array
     */
    protected function applyDatePart(array $data)
    {
        $field = $this->modifyFieldToDayWithMonth($data['field'], $data);

        switch ($data['part']) {
            case DateModifierInterface::PART_MONTH:
                $field = $this->getEnforcedTimezoneFunction('MONTH', $field);
                break;
            case DateModifierInterface::PART_DOW:
                $field = $this->getEnforcedTimezoneFunction('DAYOFWEEK', $field);
                break;
            case DateModifierInterface::PART_WEEK:
                $field = $this->getEnforcedTimezoneFunction('WEEK', $field);
                break;
            case DateModifierInterface::PART_DAY:
                $field = $this->getEnforcedTimezoneFunction('DAY', $field);
                break;
            case DateModifierInterface::PART_QUARTER:
                $field = $this->getEnforcedTimezoneFunction('QUARTER', $field);
                break;
            case DateModifierInterface::PART_DOY:
                $field = $this->getEnforcedTimezoneFunction('DAYOFYEAR', $field);
                break;
            case DateModifierInterface::PART_YEAR:
                $field = $this->getEnforcedTimezoneFunction('YEAR', $field);
                break;
            case DateModifierInterface::PART_VALUE:
            default:
                break;
        }

        return array_merge($data, ['field' => $field]);
    }

    /**
     * variable 'this day without year' search all today records without year
     * text 'January 16' search all January 16 records without year
     *
     * @param string $sqlField
     * @param array $data
     * @return string
     */
    protected function modifyFieldToDayWithMonth($sqlField, array &$data)
    {
        $isModifyAllowed = false;
        $fields = ['date_start', 'date_end'];
        foreach ($fields as $field) {
            $originalKey = $field.'_original';
            if ($this->allowToModifyFieldToDayWithMonth($data, $originalKey, $field)) {
                    $data[$field] = $data[$field]
                        ->setTimezone(new \DateTimeZone($this->localeSettings->getTimeZone()))
                        ->format('md');
                    $isModifyAllowed = true;
            }
        }

        if ($isModifyAllowed) {
            $sqlField = $this->applyTimezoneConvert($sqlField);
            $sqlField = sprintf('MONTH(%1$s) * 100 + DAY(%1$s)', $sqlField);
        }

        return $sqlField;
    }

    /**
     * Allow to modify sql field if present 'this day without year' variable or date without year
     *
     * @param array $data
     * @param string $originalKey
     * @param string $field
     * @return bool
     */
    protected function allowToModifyFieldToDayWithMonth($data, $originalKey, $field)
    {
        if (!$data[$originalKey]) {
            return false;
        }
        $expression = $this->expressionCompiler->compile($data[$originalKey], true);
        return
            $expression instanceof ExpressionResult &&
            $data[$field] instanceof \DateTime &&
            ($expression->getVariableType() == DateModifierInterface::VAR_THIS_DAY_W_Y ||
             $expression->getSourceType() == ExpressionResult::TYPE_DAYMONTH);
    }

    /**
     * Check whenever user timezone not UTC then wrap field name with convert timezone func
     *
     * @param string $functionName
     * @param string $fieldName
     *
     * @return string
     */
    private function getEnforcedTimezoneFunction($functionName, $fieldName)
    {
        $result = sprintf('%s(%s)', $functionName, $this->applyTimezoneConvert($fieldName));

        return $result;
    }

    /**
     * Correcting time zone in case if it not equals UTC
     *
     * @param string $fieldName
     * @return string
     */
    protected function applyTimezoneConvert($fieldName)
    {
        if ('UTC' !== $this->localeSettings->getTimeZone()) {
            $fieldName = sprintf("CONVERT_TZ(%s, '+00:00', '%s')", $fieldName, $this->getTimeZoneOffset());
        }

        return $fieldName;
    }

    /**
     * Get current timezone offset
     *
     * @return string
     */
    private function getTimeZoneOffset()
    {
        if (null === $this->offset) {
            $time         = new \DateTime('now', new \DateTimeZone($this->localeSettings->getTimeZone()));
            $this->offset = $time->format('P');
        }

        return $this->offset;
    }
}
