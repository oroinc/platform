<?php

namespace Oro\Bundle\DashboardBundle\Provider\BigNumber;

use Oro\Bundle\FilterBundle\Form\Type\Filter\AbstractDateFilterType;
use Oro\Bundle\FilterBundle\Provider\DateModifierInterface;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * Provides a set of reusable utility methods for "big numbers" dashboard widgets
 * to simplify a work with time periods by which the widget's data is filtered.
 */
class BigNumberDateHelper
{
    /** @var RegistryInterface */
    protected $doctrine;

    /** @var AclHelper */
    protected $aclHelper;

    /** @var LocaleSettings */
    protected $localeSettings;

    /**
     * @param RegistryInterface $doctrine
     * @param AclHelper         $aclHelper
     * @param LocaleSettings    $localeSettings
     */
    public function __construct(RegistryInterface $doctrine, AclHelper $aclHelper, LocaleSettings $localeSettings)
    {
        $this->doctrine       = $doctrine;
        $this->aclHelper      = $aclHelper;
        $this->localeSettings = $localeSettings;
    }

    /**
     * @param array  $dateRange
     * @param string $entity
     * @param string $field
     *
     * @return \DateTime[]
     */
    public function getPeriod($dateRange, $entity, $field)
    {
        $start = $dateRange['start'];
        $end   = $dateRange['end'];
        if ((isset($dateRange['type']) && $dateRange['type'] === AbstractDateFilterType::TYPE_LESS_THAN)
            || (isset($dateRange['part']) && $dateRange['part'] === DateModifierInterface::PART_ALL_TIME)
        ) {
            $qb    = $this->doctrine
                ->getRepository($entity)
                ->createQueryBuilder('e')
                ->select(sprintf('MIN(e.%s) as val', $field));
            $start = $this->aclHelper->apply($qb)->getSingleScalarResult();
            $start = new \DateTime($start, new \DateTimeZone('UTC'));
            $start->setTimezone(new \DateTimeZone($this->localeSettings->getTimeZone()));
        }

        return [$start, $end];
    }

    /**
     * @param integer $weeksDiff
     *
     * @return \DateTime[]
     */
    public function getLastWeekPeriod($weeksDiff = 0)
    {
        $lastDayNumber  = $this->localeSettings->getCalendar()->getFirstDayOfWeek() + 5;
        $lastDayString = date('l', strtotime("Sunday +{$lastDayNumber} days"));

        $end = new \DateTime('last ' . $lastDayString, new \DateTimeZone($this->localeSettings->getTimeZone()));
        $end->setTime(0, 0, 0)->modify('1 day');
        $start = clone $end;
        $start->modify('-7 days');

        if ($weeksDiff) {
            $days = $weeksDiff * 7;
            $start->modify("{$days} days");
            $end->modify("{$days} days");
        }

        return [
            'start' => $start,
            'end'   => $end
        ];
    }
}
