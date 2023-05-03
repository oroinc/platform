<?php

namespace Oro\Bundle\DashboardBundle\Helper;

use Carbon\Carbon;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FilterBundle\Form\Type\Filter\AbstractDateFilterType;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

/**
 * Provides a set of reusable utility methods for dashboard widgets
 * to simplify a work with time periods by which the widget's data is filtered.
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class DateHelper
{
    public const MIN_DATE = '1900-01-01';

    private const DAYS_IN_53_WEEKS = 371;
    private const YEAR_TYPE_DAYS  = 1460;
    private const MONTH_TYPE_DAYS = 93;
    private const WEEK_TYPE_DAYS  = 60;
    private const DAY_TYPE_DAYS   = 2;

    /** @var string */
    protected $offset;

    /** @var LocaleSettings */
    protected $localeSettings;

    /** @var ManagerRegistry */
    protected $doctrine;

    /** @var AclHelper */
    protected $aclHelper;

    public function __construct(LocaleSettings $localeSettings, ManagerRegistry $doctrine, AclHelper $aclHelper)
    {
        $this->doctrine       = $doctrine;
        $this->localeSettings = $localeSettings;
        $this->aclHelper      = $aclHelper;
    }

    /**
     * @param \DateTime $from
     * @param \DateTime $to
     * @param array $data
     * @param string $rowKey
     * @param string $dataKey
     * @param bool $isConvertEmptyData
     * @param ?string $scaleType
     *
     * @return array
     */
    public function convertToCurrentPeriod(
        \DateTime $from,
        \DateTime $to,
        array $data,
        string $rowKey,
        string $dataKey,
        bool $isConvertEmptyData = false,
        string $scaleType = null
    ): array {
        if ($isConvertEmptyData === false && empty($data)) {
            return [];
        }

        $items = $this->getDatePeriod($from, $to, $scaleType);
        foreach ($data as $row) {
            $key                   = $this->getKey($from, $to, $row, $scaleType);
            $items[$key][$dataKey] = $row[$rowKey];
        }

        return array_values($items);
    }

    /**
     * @param \DateTime $from
     * @param \DateTime $to
     * @param array     $data
     * @param string    $rowKey
     * @param string    $dataKey
     *
     * @return array
     */
    public function combinePreviousDataWithCurrentPeriod(\DateTime $from, \DateTime $to, array $data, $rowKey, $dataKey)
    {
        if (empty($data)) {
            return [];
        }

        $currentFrom = $to;
        $currentTo   = clone $to;
        $diff        = $to->getTimestamp() - $from->getTimestamp();
        $currentTo->setTimestamp($currentFrom->getTimestamp() + $diff);

        $currentItems = $this->getDatePeriod($currentFrom, $currentTo);

        $previousItems = $this->getDatePeriod($from, $to);

        // Adjust count of items(intervals) to match
        // If count of current items > count of previous items
        // null value is adding to the previous items
        // and first item of the previous items is dropping otherwise
        $countCurrentItems = count($currentItems);
        $countItems        = count($previousItems);
        if ($countCurrentItems != $countItems) {
            $items     = [];
            $itemsKeys = array_keys($previousItems);
            for ($i = 0; $i < $countCurrentItems; $i++) {
                if (isset($itemsKeys[$i])) {
                    $key         = $itemsKeys[$i];
                    $items[$key] = $previousItems[$key];
                } else {
                    $items[] = null;
                }
            }
        } else {
            $items = $previousItems;
        }

        foreach ($data as $row) {
            $key = $this->getKey($from, $to, $row);
            if (isset($items[$key])) {
                $items[$key][$dataKey] = $row[$rowKey];
            }
        }

        $mixedItems = array_combine(array_keys($currentItems), array_values($items));
        foreach ($mixedItems as $currentDate => $previousData) {
            $previousData['date'] = $currentItems[$currentDate]['date'];
            if (isset($currentItems[$currentDate])) {
                $currentItems[$currentDate] = $previousData;
            }
        }

        return array_values($currentItems);
    }

    /**
     * @param \DateTime $start
     * @param \DateTime $end
     * @param ?string $scaleType
     *
     * @return array
     */
    public function getDatePeriod(\DateTime $start, \DateTime $end, string $scaleType = null)
    {
        $start      = clone $start;
        $end        = clone $end;
        $config     = $scaleType
            ? $this->getDateIntervalConfigByScaleType($scaleType)
            : $this->getFormatStrings($start, $end);
        $interval   = new \DateInterval($config['intervalString']);
        $datePeriod = new \DatePeriod($start, $interval, $end);
        $increment  = 0;
        $dates      = [];
        // create dates by date period
        /** @var \DateTime $dt */
        foreach ($datePeriod as $dt) {
            $key = $dt->format($config['valueStringFormat']);
            $dateItem = [
                'date' => $this->getFormattedLabel($config, $dt, $increment),
            ];
            if ($config['viewType'] === 'date') {
                $dateItem['dateStart'] = $this->getFormattedLabel($config, $dt, $increment);
                $dateItemDateEnd = (clone $dt)->modify('Sunday this week');
                $dateItem['dateEnd'] = $this->getFormattedLabel(
                    $config,
                    $dateItemDateEnd->diff($end)->invert === 0 ? $dateItemDateEnd : $end,
                    0
                );
            }
            $dates[$key] = $dateItem;
            $increment++;
        }

        $endDateKey = $end->format($config['valueStringFormat']);
        if (!in_array($endDateKey, array_keys($dates))) {
            $dateItem = [
                'date' => $this->getFormattedLabel($config, $end, $increment),
            ];
            if ($config['viewType'] === 'date') {
                $dateItem['dateStart'] = $this->getFormattedLabel(
                    $config,
                    (clone $end)->modify('Monday this week'),
                    0
                );
                $dateItem['dateEnd'] = $this->getFormattedLabel($config, $end, 0);
            }
            $dates[$endDateKey] = $dateItem;
        }

        return $dates;
    }

    /**
     * @param \DateTime $start
     * @param \DateTime $end
     * @param QueryBuilder $qb
     * @param string $entityField
     * @param ?string $scaleType
     */
    public function addDatePartsSelect(
        \DateTime $start,
        \DateTime $end,
        QueryBuilder $qb,
        string $entityField,
        string $scaleType = null
    ) {
        QueryBuilderUtil::checkField($entityField);
        $scaleType = $scaleType ?? $this->getFormatStrings($start, $end)['viewType'];
        switch ($scaleType) {
            case 'year':
                $qb->addSelect(sprintf(
                    '%s as yearCreated',
                    $this->getEnforcedTimezoneFunction('YEAR', $entityField)
                ));
                $qb->addGroupBy('yearCreated');
                $qb->addOrderBy('yearCreated');
                break;
            case 'month':
                $qb->addSelect(sprintf(
                    '%s as yearCreated',
                    $this->getEnforcedTimezoneFunction('YEAR', $entityField)
                ));
                $qb->addSelect(
                    sprintf(
                        '%s as monthCreated',
                        $this->getEnforcedTimezoneFunction('MONTH', $entityField)
                    )
                );
                $qb->addGroupBy('yearCreated');
                $qb->addGroupBy('monthCreated');
                $qb->addOrderBy('yearCreated');
                $qb->addOrderBy('monthCreated');
                break;
            case 'date':
                $qb->addSelect(sprintf(
                    "%s as yearCreated",
                    $this->getEnforcedTimezoneFunction('ISOYEAR', $entityField)
                ));
                $qb->addSelect(sprintf(
                    '%s as weekCreated',
                    $this->getEnforcedTimezoneFunction('WEEK', $entityField)
                ));
                $qb->addGroupBy('yearCreated');
                $qb->addGroupBy('weekCreated');
                $qb->addOrderBy('yearCreated');
                $qb->addOrderBy('weekCreated');
                break;
            case 'day':
                $qb->addSelect(sprintf(
                    "%s as yearCreated",
                    $this->getEnforcedTimezoneFunction('YEAR', $entityField)
                ));
                $qb->addSelect(
                    sprintf(
                        "%s as monthCreated",
                        $this->getEnforcedTimezoneFunction('MONTH', $entityField)
                    )
                );
                $qb->addSelect(sprintf(
                    "%s as dayCreated",
                    $this->getEnforcedTimezoneFunction('DAY', $entityField)
                ));
                $qb->addGroupBy('yearCreated');
                $qb->addGroupBy('monthCreated');
                $qb->addGroupBy('dayCreated');
                $qb->addOrderBy('yearCreated');
                $qb->addOrderBy('monthCreated');
                $qb->addOrderBy('dayCreated');
                break;
            case 'time':
                $qb->addSelect(sprintf(
                    '%s as dateCreated',
                    $this->getEnforcedTimezoneFunction('DATE', $entityField)
                ));
                $qb->addSelect(sprintf(
                    '%s as hourCreated',
                    $this->getEnforcedTimezoneFunction('HOUR', $entityField)
                ));
                $qb->addGroupBy('dateCreated');
                $qb->addGroupBy('hourCreated');
                $qb->addOrderBy('dateCreated');
                $qb->addOrderBy('hourCreated');
                break;
        }
    }

    /**
     * @param \DateTime $start
     * @param \DateTime $end
     * @param array $row
     * @param ?string $scaleType
     *
     * @return string
     */
    public function getKey(\DateTime $start, \DateTime $end, array $row, string $scaleType = null)
    {
        $config = $scaleType
            ? $this->getDateIntervalConfigByScaleType($scaleType)
            : $this->getFormatStrings($start, $end);
        switch ($config['viewType']) {
            case 'month':
                $time = strtotime(sprintf('%s-%s', $row['yearCreated'], $row['monthCreated']));
                break;
            case 'year':
                return $row['yearCreated'];
            case 'day':
                $time = strtotime(sprintf('%s-%s-%s', $row['yearCreated'], $row['monthCreated'], $row['dayCreated']));
                break;
            case 'date':
                $week = $row['weekCreated'] < 10 ? '0' . $row['weekCreated'] : $row['weekCreated'];

                return $row['yearCreated'] . '-' . $week;
            case 'time':
                return $row['dateCreated'] . '-' . str_pad($row['hourCreated'], 2, '0', STR_PAD_LEFT);
            default:
                throw new \InvalidArgumentException(sprintf('Unsupported scale "%s"', $config['viewType']));
        }

        return date($config['valueStringFormat'], $time);
    }

    /**
     * @param \DateTime $start
     * @param \DateTime $end
     *
     * @return array
     */
    public function getFormatStrings(\DateTime $start, \DateTime $end)
    {
        $diff = $end->diff($start);

        if ($diff->days >= self::YEAR_TYPE_DAYS) {
            $intervalString    = 'P1Y';
            $valueStringFormat = 'Y';
            $viewType          = 'year';
        } elseif ($diff->days > self::MONTH_TYPE_DAYS) {
            $intervalString    = 'P1M';
            $valueStringFormat = 'Y-m';
            $viewType          = 'month';
        } elseif ($diff->days > self::WEEK_TYPE_DAYS) {
            $intervalString    = 'P1W';
            $valueStringFormat = 'o-W'; // ISO 8601 week-numbering year, ISO 8601 week number of year
            $viewType          = 'date';
        } elseif ($diff->days > self::DAY_TYPE_DAYS) {
            $intervalString    = 'P1D';
            $valueStringFormat = 'Y-m-d';
            $viewType          = 'day';
        } else {
            $intervalString    = 'PT1H';
            $valueStringFormat = 'Y-m-d-H';
            $viewType          = 'time';
        }

        return [
            'intervalString'    => $intervalString,
            'valueStringFormat' => $valueStringFormat,
            'viewType'          => $viewType
        ];
    }

    /**
     * Returns correct date period dates for cases if user select 'less than' or 'all time' period type
     *
     * @param array  $dateRange Selected date range
     * @param string $entity    Entity name to search min date
     * @param string $field     Field name to search min date
     *
     * @return array
     */
    public function getPeriod(
        array $dateRange,
        string $entity,
        string $field,
        bool $isFullStartDate = false
    ): array {
        $start = $dateRange['start'];
        $end   = $dateRange['end'];

        if ($dateRange['type'] === AbstractDateFilterType::TYPE_LESS_THAN
            || $dateRange['type'] === AbstractDateFilterType::TYPE_ALL_TIME
        ) {
            QueryBuilderUtil::checkIdentifier($field);
            $qb = $this->doctrine
                ->getRepository($entity)
                ->createQueryBuilder('e')
                ->select(sprintf('COALESCE(MIN(e.%s), :defaultMinDate) as val', $field))
                ->setParameter('defaultMinDate', static::MIN_DATE);

            $start = $this->aclHelper->apply($qb)->getSingleScalarResult();
            $start = new Carbon($start, new \DateTimeZone('UTC'));
            $start = $start->setTimezone(new \DateTimeZone($this->localeSettings->getTimeZone()));

            if ($isFullStartDate) {
                $start->setTime(0, 0);
            }
        }

        return [$start, $end];
    }

    /**
     * @return \DateTime
     */
    public function getCurrentDateTime()
    {
        return new \DateTime('now', new \DateTimeZone($this->localeSettings->getTimeZone()));
    }

    /**
     * Gets date interval, depends on the user timezone and $interval.
     *
     * @param string $interval
     *
     * @return array
     */
    public function getDateTimeInterval($interval = 'P1M')
    {
        $start = $this->getCurrentDateTime();
        $start->setTime(0, 0, 0);

        $end = $this->getCurrentDateTime();
        $end->setTime(0, 0, 0)->modify('1 day');

        $start = $start->sub(new \DateInterval($interval));

        return [$start, $end];
    }

    /**
     * @param array     $config
     * @param \DateTime $date
     * @param           $increment
     *
     * @return string
     */
    protected function getFormattedLabel($config, \DateTime $date, $increment)
    {
        switch ($config['viewType']) {
            case 'year':
                return $date->format('Y');
            case 'month':
                return $date->format('Y-m-01');
            case 'date':
                if ($increment === 0) {
                    return $date->format('Y-m-d');
                }
                $wDate = new \DateTime();
                $wDate->setISODate($date->format('Y'), $date->format('W'));

                return $wDate->format('Y-m-d');
            case 'day':
                return $date->format('Y-m-d');
        }

        return (clone $date)->modify('+1 hour')->format('c');
    }

    /**
     * Check whenever user timezone not UTC then wrap field name with convert timezone func
     *
     * @param string $functionName
     * @param string $fieldName
     *
     * @return string
     */
    protected function getEnforcedTimezoneFunction($functionName, $fieldName)
    {
        if ('UTC' !== $this->localeSettings->getTimeZone()) {
            $fieldName = sprintf("CONVERT_TZ(%s, '+00:00', '%s')", $fieldName, $this->getTimeZoneOffset());
        }

        return sprintf('%s(%s)', $functionName, $fieldName);
    }

    /**
     * Get current timezone offset
     *
     * @return string
     */
    protected function getTimeZoneOffset()
    {
        if (null === $this->offset) {
            $time         = new \DateTime('now', new \DateTimeZone($this->localeSettings->getTimeZone()));
            $this->offset = $time->format('P');
        }

        return $this->offset;
    }

    /**
     * @param \DateTimeInterface $start
     * @param \DateTimeInterface $end
     * @param int $dateRangeType
     *
     * @return string
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function getScaleType(\DateTimeInterface $start, \DateTimeInterface $end, int $dateRangeType): string
    {
        $diff = $end->diff($start);

        if ($diff->days <= 1) {
            return 'time';
        }

        switch ($dateRangeType) {
            case AbstractDateFilterType::TYPE_TODAY:
                return 'time';
            case AbstractDateFilterType::TYPE_THIS_WEEK:
            case AbstractDateFilterType::TYPE_THIS_MONTH:
                return 'day';
            case AbstractDateFilterType::TYPE_THIS_QUARTER:
            case AbstractDateFilterType::TYPE_THIS_YEAR:
                return 'date';
            case AbstractDateFilterType::TYPE_MORE_THAN:
            case AbstractDateFilterType::TYPE_LESS_THAN:
            case AbstractDateFilterType::TYPE_BETWEEN:
            case AbstractDateFilterType::TYPE_ALL_TIME:
                if ($diff->days <= 31) {
                    $scale = 'day';
                } elseif ($diff->days <= self::DAYS_IN_53_WEEKS) {
                    $scale = 'date';
                } else {
                    $scale = 'month';
                }

                return $scale;
            default:
                throw new \InvalidArgumentException(sprintf('Unsupported date range type "%s"', $dateRangeType));
        }
    }

    public function getDateIntervalConfigByScaleType(string $scaleType): array
    {
        switch ($scaleType) {
            case 'year':
                $intervalString = 'P1Y';
                $valueStringFormat = 'Y';
                break;
            case 'month':
                $intervalString = 'P1M';
                $valueStringFormat = 'Y-m';
                break;
            case 'date':
                $intervalString = 'P1W';
                $valueStringFormat = 'o-W'; // ISO 8601 week-numbering year, ISO 8601 week number of year
                break;
            case 'day':
                $intervalString = 'P1D';
                $valueStringFormat = 'Y-m-d';
                break;
            case 'time':
                $intervalString = 'PT1H';
                $valueStringFormat = 'Y-m-d-H';
                break;
            default:
                throw new \InvalidArgumentException(sprintf('Unsupported scale "%s"', $scaleType));
        }

        return [
            'intervalString' => $intervalString,
            'valueStringFormat' => $valueStringFormat,
            'viewType' => $scaleType
        ];
    }
}
