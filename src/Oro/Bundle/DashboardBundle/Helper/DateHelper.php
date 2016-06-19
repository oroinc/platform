<?php

namespace Oro\Bundle\DashboardBundle\Helper;

use Doctrine\ORM\QueryBuilder;

use Symfony\Bridge\Doctrine\RegistryInterface;

use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

use Oro\Bundle\FilterBundle\Form\Type\Filter\AbstractDateFilterType;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;

class DateHelper
{
    const YEAR_TYPE_DAYS  = 1460;
    const MONTH_TYPE_DAYS = 93;
    const WEEK_TYPE_DAYS  = 60;
    const DAY_TYPE_DAYS   = 2;
    const MIN_DATE        = '1900-01-01';

    /** @var string */
    protected $offset;

    /** @var LocaleSettings */
    protected $localeSettings;

    /** @var RegistryInterface */
    protected $doctrine;

    /** @var AclHelper */
    protected $aclHelper;

    /**
     * @param LocaleSettings    $localeSettings
     * @param RegistryInterface $doctrine
     * @param AclHelper         $aclHelper
     */
    public function __construct(LocaleSettings $localeSettings, RegistryInterface $doctrine, AclHelper $aclHelper)
    {
        $this->doctrine       = $doctrine;
        $this->localeSettings = $localeSettings;
        $this->aclHelper      = $aclHelper;
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
    public function convertToCurrentPeriod(\DateTime $from, \DateTime $to, array $data, $rowKey, $dataKey)
    {
        if (empty($data)) {
            return [];
        }

        $items = $this->getDatePeriod($from, $to);
        foreach ($data as $row) {
            $key                   = $this->getKey($from, $to, $row);
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
     *
     * @return array
     */
    public function getDatePeriod(\DateTime $start, \DateTime $end)
    {
        $start      = clone $start;
        $end        = clone $end;
        $config     = self::getFormatStrings($start, $end);
        $interval   = new \DateInterval($config['intervalString']);
        $datePeriod = new \DatePeriod($start, $interval, $end);
        $increment  = 0;
        $dates      = [];
        // create dates by date period
        /** @var \DateTime $dt */
        foreach ($datePeriod as $dt) {
            $key         = $dt->format($config['valueStringFormat']);
            $dates[$key] = [
                'date' => $this->getFormattedLabel($config, $dt, $increment),
            ];
            $increment++;
        }

        $endDateKey = $end->format($config['valueStringFormat']);
        if (!in_array($endDateKey, array_keys($dates))) {
            $dates[$endDateKey] = [
                'date' => $this->getFormattedLabel($config, $end, $increment),
            ];
        }

        return $dates;
    }

    /**
     * @param \DateTime    $start
     * @param \DateTime    $end
     * @param QueryBuilder $qb
     * @param              $entityField
     */
    public function addDatePartsSelect(\DateTime $start, \DateTime $end, QueryBuilder $qb, $entityField)
    {
        switch ($this->getFormatStrings($start, $end)['viewType']) {
            case 'year':
                $qb->addSelect(sprintf('%s as yearCreated', $this->getEnforcedTimezoneFunction('YEAR', $entityField)));
                $qb->addGroupBy('yearCreated');
                break;
            case 'month':
                $qb->addSelect(sprintf('%s as yearCreated', $this->getEnforcedTimezoneFunction('YEAR', $entityField)));
                $qb->addSelect(
                    sprintf(
                        '%s as monthCreated',
                        $this->getEnforcedTimezoneFunction('MONTH', $entityField)
                    )
                );
                $qb->addGroupBy('yearCreated');
                $qb->addGroupBy('monthCreated');
                break;
            case 'date':
                $qb->addSelect(sprintf("%s as yearCreated", $this->getEnforcedTimezoneFunction('YEAR', $entityField)));
                $qb->addSelect(sprintf('%s as weekCreated', $this->getEnforcedTimezoneFunction('WEEK', $entityField)));
                $qb->addGroupBy('yearCreated');
                $qb->addGroupBy('weekCreated');
                break;
            case 'day':
                $qb->addSelect(sprintf("%s as yearCreated", $this->getEnforcedTimezoneFunction('YEAR', $entityField)));
                $qb->addSelect(
                    sprintf(
                        "%s as monthCreated",
                        $this->getEnforcedTimezoneFunction('MONTH', $entityField)
                    )
                );
                $qb->addSelect(sprintf("%s as dayCreated", $this->getEnforcedTimezoneFunction('DAY', $entityField)));
                $qb->addGroupBy('yearCreated');
                $qb->addGroupBy('monthCreated');
                $qb->addGroupBy('dayCreated');
                break;
            case 'time':
                $qb->addSelect(sprintf('%s as dateCreated', $this->getEnforcedTimezoneFunction('DATE', $entityField)));
                $qb->addSelect(sprintf('%s as hourCreated', $this->getEnforcedTimezoneFunction('HOUR', $entityField)));
                $qb->addGroupBy('dateCreated');
                $qb->addGroupBy('hourCreated');
                break;
        }
    }

    /**
     * @param \DateTime $start
     * @param \DateTime $end
     * @param           $row
     *
     * @return string
     */
    public function getKey(\DateTime $start, \DateTime $end, $row)
    {
        $config = $this->getFormatStrings($start, $end);
        switch ($config['viewType']) {
            case 'month':
                $time = strtotime(sprintf('%s-%s', $row['yearCreated'], $row['monthCreated']));
                break;
            case 'year':
                return $row['yearCreated'];
                break;
            case 'day':
                $time = strtotime(sprintf('%s-%s-%s', $row['yearCreated'], $row['monthCreated'], $row['dayCreated']));
                break;
            case 'date':
                $week = $row['weekCreated'] < 10 ? '0' . $row['weekCreated'] : $row['weekCreated'];

                return $row['yearCreated'] . '-' . $week;
                break;
            case 'time':
                return $row['dateCreated'] . '-' . $row['hourCreated'];
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
            $valueStringFormat = 'Y-W';
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
     * Returns correct date period dates for cases if user select 'less than' period type
     *
     * @param array  $dateRange selected date range
     * @param string $entity    Entity name to search min date
     * @param string $field     Field name to search min date
     *
     * @return array
     */
    public function getPeriod($dateRange, $entity, $field)
    {
        $start = $dateRange['start'];
        $end   = $dateRange['end'];

        if ($dateRange['type'] === AbstractDateFilterType::TYPE_LESS_THAN) {
            $qb = $this->doctrine
                ->getRepository($entity)
                ->createQueryBuilder('e')
                ->select(sprintf('MIN(e.%s) as val', $field));

            $start = $this->aclHelper->apply($qb)->getSingleScalarResult();
            $start = new \DateTime($start, new \DateTimeZone('UTC'));
            $start = $start->setTimezone(new \DateTimeZone($this->localeSettings->getTimeZone()));
        }
        if ($start === null && $end === null) {
            $start = new \DateTime(self::MIN_DATE, new \DateTimeZone('UTC'));
            $end   = new \DateTime('now', new \DateTimeZone('UTC'));
        }

        return [$start, $end];
    }

    /**
     * @return \DateTime
     */
    public function getCurrentDateTime()
    {
        $now = new \DateTime('now', new \DateTimeZone($this->localeSettings->getTimeZone()));

        return $now;
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
        $end->setTime(23, 59, 59);

        $start = $start->sub(new \DateInterval($interval));

        return [$start, $end];
    }

    /**
     * Gets previous date interval
     *
     * @param \DateTime $from
     * @param \DateTime $to
     *
     * @return array
     */
    public function getPreviousDateTimeInterval(\DateTime $from, \DateTime $to)
    {
        $interval = $from->diff($to);
        $start    = clone $from;
        $start    = $start->sub($interval);
        $start    = $start->sub(new \DateInterval('PT1S'));

        $end = clone $to;
        $end = $end->sub($interval);
        $end = $end->sub(new \DateInterval('PT1S'));

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

        return $date->format('c');
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
        $result = sprintf('%s(%s)', $functionName, $fieldName);

        return $result;
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
}
