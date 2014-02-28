<?php

namespace Oro\Bundle\FilterBundle\Filter;

use Carbon\Carbon;

use Symfony\Component\Form\FormFactoryInterface;

use Oro\Bundle\FilterBundle\Expression\Date\Compiler;
use Oro\Bundle\FilterBundle\Provider\DateModifierInterface;
use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Form\Type\Filter\DateRangeFilterType;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;

abstract class AbstractDateFilter extends AbstractFilter
{
    /**
     * DateTime object as string format
     */
    const DATETIME_FORMAT = 'Y-m-d';

    /**
     * @var LocaleSettings
     */
    protected $localeSettings;

    public function __construct(
        FormFactoryInterface $factory,
        FilterUtility $util,
        LocaleSettings $localeSettings = null
    ) {
        parent::__construct($factory, $util);

        $this->localeSettings = $localeSettings;
    }

    /**
     * {@inheritdoc}
     */
    public function apply(FilterDatasourceAdapterInterface $ds, $data)
    {
        $data = $this->parseData($data);
        if (!$data) {
            return false;
        }

        $dateStartValue = Carbon::parse($data['date_start'], new \DateTimeZone('UTC'));
        $dateEndValue   = Carbon::parse($data['date_end'], new \DateTimeZone('UTC'));

        $fieldName = $this->get(FilterUtility::DATA_NAME_KEY);
        $fieldName = $this->applyDatePart($data['part'], $fieldName, $dateStartValue, $dateEndValue);

        $startDateParameterName = $ds->generateParameterName($this->getName());
        $endDateParameterName   = $ds->generateParameterName($this->getName());


        $this->applyDependingOnType(
            $data['type'],
            $ds,
            $dateStartValue,
            $dateEndValue,
            $startDateParameterName,
            $endDateParameterName,
            $fieldName
        );

        if ($dateStartValue) {
            $ds->setParameter($startDateParameterName, $dateStartValue);
        }
        if ($dateEndValue) {
            $ds->setParameter($endDateParameterName, $dateEndValue);
        }

        return true;
    }

    /**
     * @param mixed $data
     *
     * @return array|bool
     */
    public function parseData($data)
    {
        if (!$this->isValidData($data)) {
            return false;
        }

        if (isset($data['value']['start'])) {
            /** @var \DateTime $startDate */
            $startDate = $data['value']['start'];
            $startDate->setTimezone(new \DateTimeZone('UTC'));
            $data['value']['start'] = $startDate->format(static::DATETIME_FORMAT);
        } else {
            $data['value']['start'] = null;
        }

        if (isset($data['value']['end'])) {
            /** @var \DateTime $endDate */
            $endDate = $data['value']['end'];
            $endDate->setTimezone(new \DateTimeZone('UTC'));
            $data['value']['end'] = $endDate->format(static::DATETIME_FORMAT);
        } else {
            $data['value']['end'] = null;
        }

        if (!isset($data['type'])) {
            $data['type'] = null;
        }

        if (!in_array(
            $data['type'],
            array(
                DateRangeFilterType::TYPE_BETWEEN,
                DateRangeFilterType::TYPE_NOT_BETWEEN,
                DateRangeFilterType::TYPE_MORE_THAN,
                DateRangeFilterType::TYPE_LESS_THAN
            )
        )
        ) {
            $data['type'] = DateRangeFilterType::TYPE_BETWEEN;
        }

        if ($data['type'] == DateRangeFilterType::TYPE_MORE_THAN) {
            $data['value']['end'] = null;
        } elseif ($data['type'] == DateRangeFilterType::TYPE_LESS_THAN) {
            $data['value']['start'] = null;
        }

        return array(
            'date_start' => $data['value']['start'],
            'date_end'   => $data['value']['end'],
            'type'       => $data['type'],
            'part'       => $data['part'],
        );
    }

    /**
     * @param $data
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

        // check start date
        if (isset($data['value']['start']) && !$data['value']['start'] instanceof \DateTime) {
            return false;
        }

        // check end date
        if (isset($data['value']['end']) && !$data['value']['end'] instanceof \DateTime) {
            return false;
        }

        return true;
    }

    /**
     * @param string           $part
     * @param string           $field
     *
     * @param Carbon|\DateTime $dateStart
     * @param Carbon|\DateTime $dateEnd
     *
     * @return mixed
     */
    protected function applyDatePart($part, $field, Carbon &$dateStart, Carbon &$dateEnd)
    {
        switch ($part) {
            case DateModifierInterface::PART_MONTH:
                $field     = sprintf('MONTH(%s)', $field);
                $dateStart = $dateStart->month;
                $dateEnd   = $dateEnd->month;
                break;
            case DateModifierInterface::PART_DOW:
                $field     = sprintf('DAYOFWEEK(%s)', $field);
                $dateStart = $dateStart->dayOfWeek;
                $dateEnd   = $dateEnd->dayOfWeek;
                break;
            case DateModifierInterface::PART_WEEK:
                $field     = sprintf('WEEK(%s)', $field);
                $dateStart = $dateStart->weekOfYear;
                $dateEnd   = $dateEnd->weekOfYear;
                break;
            case DateModifierInterface::PART_DAY:
                $field     = sprintf('DAY(%s)', $field);
                $dateStart = $dateStart->day;
                $dateEnd   = $dateEnd->day;
                break;
            case DateModifierInterface::PART_QUARTER:
                $field     = sprintf('QUARTER(%s)', $field);
                $dateStart = $dateStart->quarter;
                $dateEnd   = $dateEnd->quarter;
                break;
            case DateModifierInterface::PART_DOY:
                $field     = sprintf('DAYOFYEAR(%s)', $field);
                $dateStart = $dateStart->dayOfYear;
                $dateEnd   = $dateEnd->dayOfYear;
                break;
            case DateModifierInterface::PART_YEAR:
                $field     = sprintf('YEAR(%s)', $field);
                $dateStart = $dateStart->year;
                $dateEnd   = $dateEnd->year;
                break;
            case DateModifierInterface::PART_VALUE:
            default:
                break;
        }

        return $field;
    }

    /**
     * Apply expression using "between" filtering
     *
     * @param FilterDatasourceAdapterInterface $ds
     * @param string                           $dateStartValue
     * @param string                           $dateEndValue
     * @param string                           $startDateParameterName
     * @param string                           $endDateParameterName
     * @param string                           $fieldName
     */
    protected function applyFilterBetween(
        $ds,
        $dateStartValue,
        $dateEndValue,
        $startDateParameterName,
        $endDateParameterName,
        $fieldName
    ) {
        // check if date part applied and start date greater than end
        if ($dateStartValue > $dateEndValue && strpos($fieldName, '(') !== false) {
            $conditionType = FilterUtility::CONDITION_OR;
        } else {
            $conditionType = FilterUtility::CONDITION_AND;
        }

        if ($dateStartValue) {
            $this->applyFilterToClause(
                $ds,
                $ds->expr()->gte($fieldName, $startDateParameterName, true),
                $conditionType
            );
        }

        if ($dateEndValue) {
            $this->applyFilterToClause(
                $ds,
                $ds->expr()->lte($fieldName, $endDateParameterName, true),
                $conditionType
            );
        }
    }

    /**
     * Apply expression using one condition (less or more)
     *
     * @param FilterDatasourceAdapterInterface $ds
     * @param                                  $dateValue
     * @param                                  $dateParameterName
     * @param string                           $fieldName
     * @param bool                             $isLess less/more mode, true if 'less than', false if 'more than'
     */
    protected function applyFilterLessMore(
        $ds,
        $dateValue,
        $dateParameterName,
        $fieldName,
        $isLess
    ) {
        if ($dateValue) {
            $expr = $isLess
                ? $ds->expr()->lt($fieldName, $dateParameterName, true)
                : $ds->expr()->gt($fieldName, $dateParameterName, true);
            $this->applyFilterToClause($ds, $expr);
        }
    }

    /**
     * Apply expression using "not between" filtering
     *
     * @param FilterDatasourceAdapterInterface $ds
     * @param string                           $dateStartValue
     * @param string                           $dateEndValue
     * @param string                           $startDateParameterName
     * @param string                           $endDateParameterName
     * @param string                           $fieldName
     */
    protected function applyFilterNotBetween(
        $ds,
        $dateStartValue,
        $dateEndValue,
        $startDateParameterName,
        $endDateParameterName,
        $fieldName
    ) {
        if ($dateStartValue || $dateEndValue) {
            $expr = null;
            if ($dateStartValue) {
                if ($dateEndValue) {
                    $expr = $ds->expr()->orX(
                        $ds->expr()->lt($fieldName, $startDateParameterName, true),
                        $ds->expr()->gt($fieldName, $endDateParameterName, true)
                    );
                } else {
                    $expr = $ds->expr()->lt($fieldName, $startDateParameterName, true);
                }
            } else {
                $expr = $ds->expr()->gt($fieldName, $endDateParameterName, true);
            }
            $this->applyFilterToClause($ds, $expr);
        }
    }

    /**
     * Applies filter depending on it's type
     *
     * @param int                              $type
     * @param FilterDatasourceAdapterInterface $ds
     * @param string                           $dateStartValue
     * @param string                           $dateEndValue
     * @param string                           $startDateParameterName
     * @param string                           $endDateParameterName
     * @param                                  $fieldName
     *
     */
    protected function applyDependingOnType(
        $type,
        $ds,
        $dateStartValue,
        $dateEndValue,
        $startDateParameterName,
        $endDateParameterName,
        $fieldName
    ) {
        switch ($type) {
            case DateRangeFilterType::TYPE_MORE_THAN:
                $this->applyFilterLessMore(
                    $ds,
                    $dateStartValue,
                    $startDateParameterName,
                    $fieldName,
                    false
                );
                break;
            case DateRangeFilterType::TYPE_LESS_THAN:
                $this->applyFilterLessMore(
                    $ds,
                    $dateEndValue,
                    $endDateParameterName,
                    $fieldName,
                    true
                );
                break;
            case DateRangeFilterType::TYPE_NOT_BETWEEN:
                $this->applyFilterNotBetween(
                    $ds,
                    $dateStartValue,
                    $dateEndValue,
                    $startDateParameterName,
                    $endDateParameterName,
                    $fieldName
                );
                break;
            default:
            case DateRangeFilterType::TYPE_BETWEEN:
                $this->applyFilterBetween(
                    $ds,
                    $dateStartValue,
                    $dateEndValue,
                    $startDateParameterName,
                    $endDateParameterName,
                    $fieldName
                );
                break;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata()
    {
        $formView = $this->getForm()->createView();

        $metadata                          = parent::getMetadata();
        $metadata['typeValues']            = $formView->vars['type_values'];
        $metadata['externalWidgetOptions'] = $formView->vars['widget_options'];
        $metadata['dateParts']             = $formView->vars['date_parts'];
        $metadata['externalWidgetOptions'] = array_merge(
            $formView->vars['widget_options'],
            ['dateVars' => $formView->vars['date_vars']]
        );

        return $metadata;
    }

    /**
     * {@inheritdoc}
     */
    public function processParams($data)
    {
        $compiler = new Compiler();

        $start = $compiler->compile($data['value']['start']);
        $end   = $compiler->compile($data['value']['start']);

        if ($start instanceof Carbon) {
            $start->setTimezone(new \DateTimeZone($this->localeSettings->getTimeZone()));
        }
        if ($end instanceof Carbon) {
            $end->setTimezone(new \DateTimeZone($this->localeSettings->getTimeZone()));
        }

        $data['value']['start'] = (string)$start;
        $data['value']['end']   = (string)$end;

        return $data;
    }
}
