<?php

namespace Oro\Bundle\TrackingBundle\Processor;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

use Oro\Bundle\TrackingBundle\Entity\TrackingVisit;
use Oro\Bundle\TrackingBundle\Entity\TrackingVisitEvent;
use Oro\Bundle\TrackingBundle\Entity\TrackingEvent;

class TrackingProcessor implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    const TACKING_EVENT_ENTITY = 'OroTrackingBundle:TrackingEvent';
    const TRACKING_VISIT_ENTITY = 'OroTrackingBundle:TrackingVisit';

    const BATCH_SIZE = 100;

    /** @var ManagerRegistry */
    protected $doctrine;

    protected $collectedVisits = [];

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * Process tracking data
     */
    public function process()
    {
        if ($this->logger === null) {
            $this->logger = new NullLogger();
        }

        $this->processEntities();
    }

    /**
     * Collect new tracking visits with tracking visit events
     */
    protected function processEntities()
    {
        $queryBuilder = $this->getEntityManager()
            ->getRepository(self::TACKING_EVENT_ENTITY)
            ->createQueryBuilder('entity')
            ->where('entity.parsed = false')
            ->orderBy('entity.createdAt', 'ASC')
            ->setMaxResults(self::BATCH_SIZE);

        $entities = $queryBuilder->getQuery()->getResult();

        if ($entities) {
            $this->processTrackingVisits($entities);
            $this->processEntities();
        }
    }

    /**
     * @param array|TrackingEvent[] $entities
     */
    protected function processTrackingVisits($entities)
    {
        $this->logger->notice('Process batch');
        $em = $this->getEntityManager();

        /** @var  TrackingEvent $event */
        foreach ($entities as $event) {

            $this->logger->notice($event->getId());

            $trackingVisitEvent = new TrackingVisitEvent();

           //todo: set event
            $trackingVisitEvent->setEvent($this->getMapping()[$event->getName()]);

            $eventData = $event->getEventData();
            $decodedData = json_decode($eventData->getData());

            $trackingVisit = $this->getTrackingVisit($event, $decodedData);
            $trackingVisitEvent->setVisit($trackingVisit);
            $trackingVisitEvent->setWebEvent($event);

            $event->setParsed(true);

            $em->persist($event);
            $em->persist($trackingVisitEvent);
            $em->persist($trackingVisit);

        }

        $em->flush();
        $this->collectedVisits = [];
        $em->clear();
    }

    /**
     * @param TrackingEvent $trackingEvent
     * @param \stdClass     $decodedData
     *
     * @return TrackingVisit
     */
    protected function getTrackingVisit(TrackingEvent $trackingEvent, $decodedData)
    {
        $visitorUid = $decodedData->_id;
        $userIdentifier = $trackingEvent->getUserIdentifier();

        $hash = md5($visitorUid . $userIdentifier);

        // try to find existing visit
        if (!empty($this->collectedVisits) && array_key_exists($hash, $this->collectedVisits)) {
            $visit = $this->collectedVisits[$hash];
        } else {
            $visit = $this->doctrine->getRepository(self::TRACKING_VISIT_ENTITY)->findOneBy(
                [
                    'visitorUid' => $visitorUid,
                    'userIdentifier' => $trackingEvent->getUserIdentifier()
                ]
            );
        }

        if (!$visit) {
            $visit = new TrackingVisit();
            $visit->setParsedUID(0);
            $visit->setUserIdentifier($trackingEvent->getUserIdentifier());
            $visit->setVisitorUid($visitorUid);
            $visit->setFirstActionTime($trackingEvent->getCreatedAt());
            $visit->setLastActionTime($trackingEvent->getCreatedAt());

            // todo: add identifier relation
            //$this->identifyCustomer($visit, $trackingEvent, $decodedData);

        } else {
            if ($visit->getFirstActionTime() > $trackingEvent->getCreatedAt()) {
                $visit->setFirstActionTime($trackingEvent->getCreatedAt());
            }
            if ($visit->getLastActionTime() < $trackingEvent->getCreatedAt()) {
                $visit->setLastActionTime($trackingEvent->getCreatedAt());
            }
        }

        if (!array_key_exists($hash, $this->collectedVisits)) {
            $this->collectedVisits[$hash] = $visit;
        }

        return $visit;
    }

    /**
     * Returns default entity manager
     *
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        /** @var EntityManager $em */
        $em = $this->doctrine->getManager();
        if (!$em->isOpen()) {
            $this->doctrine->resetManager();
            $em = $this->doctrine->getManager();
        }

        return $em;
    }
}
