<?php

namespace Oro\Bundle\DashboardBundle\Provider\BigNumber;

use Symfony\Bridge\Doctrine\RegistryInterface;

use Oro\Bundle\FilterBundle\Form\Type\Filter\AbstractDateFilterType;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\FilterBundle\Provider\DateModifierInterface;

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
        // As for now week starts from Monday and ends by Sunday
        // @todo: Should be refactored in BAP-9846
        $end = new \DateTime('last Sunday', new \DateTimeZone($this->localeSettings->getTimeZone()));

        $start = clone $end;
        $start->modify('-6 days');
        $end->setTime(23, 59, 59);

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
