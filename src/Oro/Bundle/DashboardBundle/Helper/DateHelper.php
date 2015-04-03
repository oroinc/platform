<?php

namespace Oro\Bundle\DashboardBundle\Helper;

use \DateTime;

use Doctrine\ORM\QueryBuilder;

class DateHelper
{
    const YEAR_TYPE_DAYS  = 1460;
    const MONTH_TYPE_DAYS = 93;
    const WEEK_TYPE_DAYS  = 60;
    const DAY_TYPE_DAYS   = 2;

    /**
     * @param DateTime $start
     * @param DateTime $end
     * @return array
     */
    public function getDatePeriod(DateTime $start, DateTime $end)
    {
        $config         = self::getFormatStrings($start, $end);
        $interval       = new \DateInterval($config['intervalString']);
        $incrementedEnd = clone $end;
        // we should add 1 interval to the end date, because Date Period
        // iterator deletes last item if the end is DateTime object
        $incrementedEnd->add($interval);
        $datePeriod = new \DatePeriod($start, $interval, $incrementedEnd);
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

        return $dates;
    }

    /**
     * @param DateTime     $start
     * @param DateTime     $end
     * @param QueryBuilder $qb
     * @param              $entityField
     */
    public function addDatePartsSelect(DateTime $start, DateTime $end, QueryBuilder $qb, $entityField)
    {
        switch ($this->getFormatStrings($start, $end)['viewType']) {
            case 'year':
                $qb->addSelect(sprintf('YEAR(%s) as yearCreated', $entityField));
                $qb->addGroupBy('yearCreated');
                break;
            case 'month':
                $qb->addSelect(sprintf('YEAR(%s) as yearCreated', $entityField));
                $qb->addSelect(sprintf('MONTH(%s) as monthCreated', $entityField));
                $qb->addGroupBy('yearCreated');
                $qb->addGroupBy('monthCreated');
                break;
            case 'date':
                $qb->addSelect(sprintf('YEAR(%s) as yearCreated', $entityField));
                $qb->addSelect(sprintf('WEEK(%s) as weekCreated', $entityField));
                $qb->addGroupBy('yearCreated');
                $qb->addGroupBy('weekCreated');
                break;
            case 'day':
                $qb->addSelect(sprintf('YEAR(%s) as yearCreated', $entityField));
                $qb->addSelect(sprintf('MONTH(%s) as monthCreated', $entityField));
                $qb->addSelect(sprintf('DAY(%s) as dayCreated', $entityField));
                $qb->addGroupBy('yearCreated');
                $qb->addGroupBy('monthCreated');
                $qb->addGroupBy('dayCreated');
                break;
            case 'time':
                $qb->addSelect(sprintf('DATE(%s) as dateCreated', $entityField));
                $qb->addSelect(sprintf('HOUR(%s) as hourCreated', $entityField));
                $qb->addGroupBy('dateCreated');
                $qb->addGroupBy('hourCreated');
                break;
        }
    }

    /**
     * @param DateTime $start
     * @param DateTime $end
     * @param          $row
     * @return string
     */
    public function getKey(DateTime $start, DateTime $end, $row)
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

        return date(
            $config['valueStringFormat'],
            $time
        );
    }

    /**
     * @param DateTime $start
     * @param DateTime $end
     * @return array
     */
    public function getFormatStrings(DateTime $start, DateTime $end)
    {
        $diff = $end->diff($start);

        if ($diff->days >= self::YEAR_TYPE_DAYS) { // 4 years
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
     * @param          $config
     * @param DateTime $date
     * @param          $increment
     * @return string
     */
    protected function getFormattedLabel($config, DateTime $date, $increment)
    {
        switch ($config['viewType']) {
            case 'year':
                return $date->format('Y');
                break;
            case 'month':
                return $date->format('Y-m-01');
                break;
            case 'date':
                if ($increment === 0) {
                    return $date->format('Y-m-d');
                }
                $wDate = new \DateTime();
                $wDate->setISODate($date->format('Y'), $date->format('W'));
                return $wDate->format('Y-m-d');
                break;
            case 'day':
                return $date->format('Y-m-d');
                break;
            case 'time':
                return $date->format('c');
        }
    }
}
