<?php

namespace Oro\Bundle\DashboardBundle\Helper;

use \DateTime;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatter;


class DateHelper
{
    /** @var DateTimeFormatter */
    protected $dateTimeFormatter;

    /**
     * @param DateTimeFormatter $dateTimeFormatter
     */
    public function __construct(DateTimeFormatter $dateTimeFormatter)
    {
        $this->dateTimeFormatter = $dateTimeFormatter;
    }

    /**
     * @param DateTime $start
     * @param DateTime $end
     * @return array
     */
    public function getDatePeriod(DateTime $start, DateTime $end)
    {
        $config = self::getFormatStrings($start, $end);
        $interval = new \DateInterval($config['intervalString']);
        $incrementedEnd = clone $end;
        // we should add 1 interval to the end date, because Date Period
        // iterator deletes last item if the end is DateTime object
        $incrementedEnd->add($interval);
        $datePeriod = new \DatePeriod($start, $interval, $incrementedEnd);
        $increment = 0;
        $dates = [];
        // create dates by date period
        /** @var \DateTime $dt */
        foreach ($datePeriod as $dt) {
            $key = $dt->format($config['valueStringFormat']);
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
        switch ($this->getFormatStrings($start, $end)['chartType']) {
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
            case 'week':
                $qb->addSelect(sprintf('YEAR(%s) as yearCreated', $entityField));
                $qb->addSelect(sprintf('WEEK(%s) as weekCreated', $entityField));
                $qb->addGroupBy('yearCreated');
                $qb->addGroupBy('weekCreated');
                break;
            case 'date':
                $qb->addSelect(sprintf('YEAR(%s) as yearCreated', $entityField));
                $qb->addSelect(sprintf('MONTH(%s) as monthCreated', $entityField));
                $qb->addSelect(sprintf('DAY(%s) as dayCreated', $entityField));
                $qb->addGroupBy('yearCreated');
                $qb->addGroupBy('monthCreated');
                $qb->addGroupBy('dayCreated');
                break;
            case 'hour':
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

        switch ($config['chartType']) {
            case 'month':
                $time = strtotime(sprintf('%s-%s', $row['yearCreated'], $row['monthCreated']));
                break;
            case 'year':
                return $row['yearCreated'];
                break;
            case 'date':
                $time = strtotime(sprintf('%s-%s-%s', $row['yearCreated'], $row['monthCreated'], $row['dayCreated']));
                break;
            case 'week':
                $week = $row['weekCreated'] < 10 ? '0' . $row['weekCreated'] : $row['weekCreated'];
                return $row['yearCreated'] . '-' . $week;
                break;
            case 'hour':
                return $row['dateCreated'] . '-' .$row['hourCreated'];
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

        if ($diff->days >= 1460) { // 4 years
            $intervalString = 'P1Y';
            $valueStringFormat = 'Y';
            $chartType = 'year';
            $viewType = 'year';
        } elseif ($diff->days > 93) {
            $intervalString = 'P1M';
            $valueStringFormat = 'Y-m';
            $chartType = 'month';
            $viewType = 'month';
        } elseif ($diff->days > 60) {
            $intervalString = 'P1W';
            $valueStringFormat = 'Y-W';
            $chartType = 'week';
            $viewType = 'date';
        } elseif ($diff->days > 2) {
            $intervalString = 'P1D';
            $valueStringFormat = 'Y-m-d';
            $chartType = 'date';
            $viewType = 'date';
        } else {
            $intervalString = 'PT1H';
            $valueStringFormat = 'Y-m-d-H';
            $chartType = 'hour';
            $viewType = 'time';
        }

        return [
            'intervalString' => $intervalString,
            'valueStringFormat' => $valueStringFormat,
            'chartType' => $chartType,
            'viewType' => $viewType
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
        switch ($config['chartType']) {
            case 'year':
                return $date->format('Y');
                break;
            case 'month':
                return $date->format('Y-m-01');
                break;
            case 'week':
                if ($increment === 0) {
                    return $date->format('Y-m-d');
                }
                $wDate = new \DateTime();
                $wDate->setISODate($date->format('Y'), $date->format('W'));

                return $wDate->format('Y-m-d');
                break;
            case 'date':
                return $date->format('Y-m-d');
                break;
            case 'hour':
                return $date->format('c');
        }
    }
}
