<?php

namespace Oro\Bundle\TrackingBundle\Processor;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

use Oro\Bundle\TrackingBundle\Entity\TrackingEventDictionary;
use Oro\Bundle\TrackingBundle\Entity\TrackingEvent;
use Oro\Bundle\TrackingBundle\Entity\TrackingVisit;
use Oro\Bundle\TrackingBundle\Entity\TrackingVisitEvent;

class TrackingProcessor implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    const TACKING_EVENT_ENTITY = 'OroTrackingBundle:TrackingEvent';
    const TRACKING_VISIT_ENTITY = 'OroTrackingBundle:TrackingVisit';

    const BATCH_SIZE = 100;

    /** @var ManagerRegistry */
    protected $doctrine;

    /** @var array */
    protected $collectedVisits = [];

    /** @var array */
    protected $eventDictionary = [];

    /**
     * @param ManagerRegistry $doctrine
     */
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

            $trackingVisitEvent->setEvent($this->getEventType($event));

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
        $this->eventDictionary = [];
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
            $visit->setTrackingWebsite($trackingEvent->getWebsite());
            $visit->setUserIdentifier($trackingEvent->getUserIdentifier());
            $visit->setVisitorUid($visitorUid);
            $visit->setFirstActionTime($trackingEvent->getCreatedAt());
            $visit->setLastActionTime($trackingEvent->getCreatedAt());

            $this->identifyTrackingVisit($visit, $trackingEvent, $decodedData);

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
     * Get Event dictionary for given tracking event
     *
     * @param TrackingEvent $event
     * @return TrackingEventDictionary
     */
    protected function getEventType(TrackingEvent $event)
    {
        if (isset($this->eventDictionary[$event->getWebsite()->getId()])
            && isset($this->eventDictionary[$event->getWebsite()->getId()][$event->getName()])
        ) {
            $eventType = $this->eventDictionary[$event->getWebsite()->getId()][$event->getName()];
        } else {
            $eventType = $this->getEntityManager()->getRepository('OroTrackingBundle:TrackingEventDictionary')
                ->findOneBy(
                    [
                        'name' => $event->getName(),
                        'website' => $event->getWebsite()
                    ]
                );
        }

        if (!$eventType) {
            $eventType = new TrackingEventDictionary();
            $eventType->setName($event->getName());
            $eventType->setWebsite($event->getWebsite());

            $this->getEntityManager()->persist($eventType);
            $this->eventDictionary[$event->getWebsite()->getId()][$event->getName()] = $eventType;
        }

        return $eventType;
    }

    /**
     * @param TrackingVisit $visit
     * @param TrackingEvent $event
     * @param               $unserializedData
     */
    protected function identifyTrackingVisit(TrackingVisit $visit, TrackingEvent $event, $unserializedData)
    {
        //todo: refactor this method
        $idArray = explode('; ', $event->getUserIdentifier());
        $idData = [];
        array_walk(
            $idArray,
            function($string) use (&$idData){
                $data = explode('=', $string);
                $idData[$data[0]] = $data[1];
            }
        );

        if (array_key_exists('id', $idData) && $idData['id'] !== 'guest') {
            $customerId = $idData['id'];
            $visit->setParsedUID($customerId);

            $customer = $this->getEntityManager()->getRepository('OroCRMMagentoBundle:Customer')
                ->findOneBy(['originId' => $customerId]);
            if ($customer) {
                $visit->setIdentifierTarget($customer);
                $this->identifyPrevVisits($visit, $customer);
            }
        }
    }

    protected function identifyPrevVisits(TrackingVisit $visit, $identifier)
    {
        /** @var QueryBuilder $qb */
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('entity');
        $visitors = $qb->from('OroTrackingBundle:TrackingVisit', 'entity')
            ->where('entity.visitor = :visitor')
            ->andWhere('entity.firstActionTime < :maxDate')
            ->andWhere('entity.customer is null')
            ->andWhere('entity.trackingWebsite  = :website')
            ->setParameter('visitor', $visit->getVisitorUid())
            ->setParameter('maxDate', $visit->getFirstActionTime())
            ->setParameter('website', $visit->getTrackingWebsite())
            ->getQuery()
            ->getResult();

        if (!empty($visitors)) {
            /** @var TrackingVisit $visitorObject */
            foreach ($visitors as $visitorObject) {
                $visitorObject->setIdentifierTarget($identifier);
                $this->getEntityManager()->persist($visitorObject);
            }
        }
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
