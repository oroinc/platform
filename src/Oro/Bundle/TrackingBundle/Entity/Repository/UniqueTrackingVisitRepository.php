<?php

namespace Oro\Bundle\TrackingBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\TrackingBundle\Entity\TrackingVisit;
use Oro\Bundle\TrackingBundle\Entity\UniqueTrackingVisit;

class UniqueTrackingVisitRepository extends EntityRepository
{
    /**
     * @param TrackingVisit $trackingVisit
     * @param \DateTimeZone $timeZone
     * @return null|object|UniqueTrackingVisit
     */
    public function logTrackingVisit(TrackingVisit $trackingVisit, \DateTimeZone $timeZone)
    {
        $uniqueRecord = $this->getUniqueRecordByTrackingVisit($trackingVisit, $timeZone);
        if ($uniqueRecord) {
            $uniqueRecord->increaseVisitCount();
        } else {
            $uniqueRecord = new UniqueTrackingVisit();
            $uniqueRecord->setTrackingWebsite($trackingVisit->getTrackingWebsite());
            $uniqueRecord->setUserIdentifier(md5($trackingVisit->getUserIdentifier()));
            $uniqueRecord->setVisitCount(1);
            $uniqueRecord->setFirstActionTime($this->getDateInSystemTimezone($trackingVisit, $timeZone));
            $this->getEntityManager()->persist($uniqueRecord);
        }

        return $uniqueRecord;
    }

    /**
     * @param TrackingVisit $trackingVisit
     * @param \DateTimeZone $timeZone
     * @return null|object|UniqueTrackingVisit
     */
    public function getUniqueRecordByTrackingVisit(TrackingVisit $trackingVisit, \DateTimeZone $timeZone)
    {
        return $this->findOneBy([
            'userIdentifier' => md5($trackingVisit->getUserIdentifier()),
            'firstActionTime' => $this->getDateInSystemTimezone($trackingVisit, $timeZone)
        ]);
    }

    /**
     * @param TrackingVisit $trackingVisit
     * @param \DateTimeZone $timeZone
     * @return \DateTime
     */
    private function getDateInSystemTimezone(TrackingVisit $trackingVisit, \DateTimeZone $timeZone)
    {
        $trackingDate = clone $trackingVisit->getFirstActionTime();
        $trackingDate->setTimezone($timeZone);

        return \DateTime::createFromFormat('Y-m-d', $trackingDate->format('Y-m-d'), new \DateTimeZone('UTC'));
    }
}
