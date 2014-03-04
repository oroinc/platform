<?php

namespace Oro\Bundle\FilterBundle\Filter;

use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

use Oro\Bundle\FilterBundle\Expression\Date\Compiler;
use Oro\Bundle\FilterBundle\Provider\DateModifierInterface;
use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Form\Type\Filter\DateRangeFilterType;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;

abstract class AbstractDateFilter extends AbstractFilter
{
    /** DateTime object as string format */
    const DATETIME_FORMAT = 'Y-m-d';

    /** @var LocaleSettings */
    protected $localeSettings;

    /** @var Compiler */
    protected $expressionCompiler;

    public function __construct(
        FormFactoryInterface $factory,
        FilterUtility $util,
        Compiler $compiler,
        LocaleSettings $localeSettings
    ) {
        parent::__construct($factory, $util);

        $this->expressionCompiler = $compiler;
        $this->localeSettings     = $localeSettings;
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

        $dateStartValue = $data['date_start'];
        $dateEndValue   = $data['date_end'];

        $startDateParameterName = $ds->generateParameterName($this->getName());
        $endDateParameterName   = $ds->generateParameterName($this->getName());

        $this->applyDependingOnType(
            $data['type'],
            $ds,
            $dateStartValue,
            $dateEndValue,
            $startDateParameterName,
            $endDateParameterName,
            $data['field']
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
            'field'      => $this->get(FilterUtility::DATA_NAME_KEY)
        ];
        $data = $this->applyDatePart($data);

        return $data;
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

    protected function applyDatePart($data)
    {
        $dateStart = $data['date_start'];
        $dateEnd   = $data['date_end'];
        $field     = $data['field'];
        switch ($data['part']) {
            case DateModifierInterface::PART_MONTH:
                $field     = sprintf('MONTH(%s)', $data['field']);
                $dateStart = $this->getDatePartValue($dateStart, 'm');
                $dateEnd   = $this->getDatePartValue($dateEnd, 'm');
                break;
            case DateModifierInterface::PART_DOW:
                $field     = sprintf('DAYOFWEEK(%s)', $field);
                $dateStart = $this->getDatePartValue($dateStart, 'N');
                $dateEnd   = $this->getDatePartValue($dateEnd, 'N');
                break;
            case DateModifierInterface::PART_WEEK:
                $field     = sprintf('WEEK(%s)', $field);
                $dateStart = $this->getDatePartValue($dateStart, 'W');
                $dateEnd   = $this->getDatePartValue($dateEnd, 'W');
                break;
            case DateModifierInterface::PART_DAY:
                $field     = sprintf('DAY(%s)', $field);
                $dateStart = $this->getDatePartValue($dateStart, 'd');
                $dateEnd   = $this->getDatePartValue($dateEnd, 'd');
                break;
            case DateModifierInterface::PART_QUARTER:
                $field     = sprintf('QUARTER(%s)', $field);
                $dateStart = $this->getDatePartValue($dateStart, 'm');
                $dateEnd   = $this->getDatePartValue($dateEnd, 'm');
                $dateStart = $dateStart ? ceil($dateStart / 3) : $dateStart;
                $dateEnd   = $dateEnd ? ceil($dateEnd / 3) : $dateEnd;
                break;
            case DateModifierInterface::PART_DOY:
                $field     = sprintf('DAYOFYEAR(%s)', $field);
                $dateStart = $this->getDatePartValue($dateStart, 'z');
                $dateEnd   = $this->getDatePartValue($dateEnd, 'z');
                break;
            case DateModifierInterface::PART_YEAR:
                $field     = sprintf('YEAR(%s)', $field);
                $dateStart = $this->getDatePartValue($dateStart, 'Y');
                $dateEnd   = $this->getDatePartValue($dateEnd, 'Y');
                break;
            case DateModifierInterface::PART_VALUE:
            default:
                break;
        }

        return array_merge(
            $data,
            [
                'date_start' => $dateStart,
                'date_end'   => $dateEnd,
                'field'      => $field
            ]
        );
    }

    /**
     * @param mixed  $value
     * @param string $part
     *
     * @return integer|null
     * @throws \Symfony\Component\Validator\Exception\UnexpectedTypeException
     */
    private function getDatePartValue($value, $part)
    {
        switch (true) {
            case is_integer($value) || is_null($value):
                return $value;
                break;
            case ($value instanceof \DateTime):
                return $value->format($part);
                break;
            default:
                throw new UnexpectedTypeException($value, 'integer or \DateTime');
        }
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
        $start = $this->expressionCompiler->compile($data['value']['start']);
        $end   = $this->expressionCompiler->compile($data['value']['end']);

        if ($start instanceof \DateTime) {
            $start->setTimezone(new \DateTimeZone($this->localeSettings->getTimeZone()));
        }
        if ($end instanceof \DateTime) {
            $end->setTimezone(new \DateTimeZone($this->localeSettings->getTimeZone()));
        }

        $data['value']['start'] = (string)$start;
        $data['value']['end']   = (string)$end;

        return $data;
    }
}
