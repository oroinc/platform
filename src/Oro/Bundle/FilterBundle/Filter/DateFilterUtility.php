<?php

namespace Oro\Bundle\FilterBundle\Filter;

use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\FilterBundle\Provider\DateModifierInterface;
use Oro\Bundle\FilterBundle\Form\Type\Filter\DateRangeFilterType;

class DateFilterUtility
{
    /** @var LocaleSettings */
    protected $localeSettings;

    /** @var string */
    private $offset;

    /**
     * @param LocaleSettings $localeSettings
     */
    public function __construct(LocaleSettings $localeSettings)
    {
        $this->localeSettings = $localeSettings;
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
        } elseif ($data['type'] == DateRangeFilterType::TYPE_LESS_THAN) {
            $data['value']['start'] = null;
        }

        $data = [
            'date_start' => $data['value']['start'],
            'date_end'   => $data['value']['end'],
            'type'       => $data['type'],
            'part'       => isset($data['part']) ? $data['part'] : DateModifierInterface::PART_VALUE,
            'field'      => $field
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
    protected function applyDatePart($data)
    {
        $field = $data['field'];
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
     * Check whenever user timezone not UTC then wrap field name with convert timezone func
     *
     * @param string $functionName
     * @param string $fieldName
     *
     * @return string
     */
    private function getEnforcedTimezoneFunction($functionName, $fieldName)
    {
        if ('UTC' !== $this->localeSettings->getTimeZone()) {
            $fieldName = sprintf("CONVERT_TZ(%s, '+00:00', '%s')", $fieldName, $this->getTimeZoneOffset());
        }
        $result = sprintf('%s(%s)', $functionName, $fieldName);

        return $result;
    }

    /**
     * Get current timezone offset
     *
     * @return string
     */
    private function getTimeZoneOffset()
    {
        if (null === $this->offset) {
            $time = new \DateTime('now', new \DateTimeZone($this->localeSettings->getTimeZone()));
            $this->offset = $time->format('P');
        }

        return $this->offset;
    }
}
