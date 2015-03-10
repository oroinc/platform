<?php

namespace Oro\Bundle\TrackingBundle\Processor;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\Common\Util\ClassUtils;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\TrackingBundle\Entity\TrackingEventDictionary;
use Oro\Bundle\TrackingBundle\Entity\TrackingEvent;
use Oro\Bundle\TrackingBundle\Entity\TrackingVisit;
use Oro\Bundle\TrackingBundle\Entity\TrackingVisitEvent;
use Oro\Bundle\TrackingBundle\Migration\Extension\IdentifierEventExtension;

use Oro\Bundle\TrackingBundle\Provider\TrackingEventIdentificationProvider;

class TrackingProcessor implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    const TACKING_EVENT_ENTITY  = 'OroTrackingBundle:TrackingEvent';
    const TRACKING_VISIT_ENTITY = 'OroTrackingBundle:TrackingVisit';

    const BATCH_SIZE = 100;

    /** @var ManagerRegistry */
    protected $doctrine;

    /** @var array */
    protected $collectedVisits = [];

    /** @var array */
    protected $eventDictionary = [];

    /** @var  TrackingEventIdentificationProvider */
    protected $trackingIdentification;

    /**
     * @param ManagerRegistry                     $doctrine
     * @param TrackingEventIdentificationProvider $trackingIdentification
     */
    public function __construct(ManagerRegistry $doctrine, TrackingEventIdentificationProvider $trackingIdentification)
    {
        $this->doctrine               = $doctrine;
        $this->trackingIdentification = $trackingIdentification;
    }

    /**
     * Process tracking data
     */
    public function process()
    {
        /**
         *  to avoid memory leaks, we turn off doctrine logger
         */
        $this->getEntityManager()->getConnection()->getConfiguration()->setSQLLogger(null);

        if ($this->logger === null) {
            $this->logger = new NullLogger();
        }

        $this->logger->notice('Process new visits');
        while ($this->processVisits()) {
            $this->logger->notice('Try to process Next batch');
        }

        $this->logger->notice('Identify previous visits identifiers');
        while ($this->identifyPrevVisits()) {
            $this->logger->notice('Try to process Next batch');
        }
    }

    protected function identifyVisits()
    {

    }

    /**
     *  Identify previous visits in case than we haven't data to identify visit previously
     */
    protected function identifyPrevVisits()
    {
        $em = $this->getEntityManager();
        $queryBuilder = $em
            ->getRepository(self::TRACKING_VISIT_ENTITY)
            ->createQueryBuilder('entity')
            ->select('entity')
            ->where('entity.identifierDetected = false')
            ->andWhere('entity.parsedUID > 0')
            ->andWhere('entity.parsingCount < 100')
            ->orderBy('entity.firstActionTime', 'ASC');


        $entities = $queryBuilder->getQuery()->getResult();

        if ($entities) {
            /** @var TrackingVisit $visit */
            foreach ($entities as $visit) {
                $this->logger->notice($visit->getId());
                $idObj = $this->trackingIdentification->identify($visit);
                if ($idObj && $idObj['targetObject']) {
                    $visit->setIdentifierTarget($idObj['targetObject']);
                    $visit->setIdentifierDetected(true);

                    $this->logger->notice('-- ' . $idObj['parsedUID']);
                    $this->logger->notice('-- ' . $idObj['targetObject']->getFirstName() . ' ' . $idObj['targetObject']->getLastName());

                } else {
                    $visit->setParsingCount($visit->getParsingCount() + 1);
                }

                $em->persist($visit);
                $this->collectedVisits[] = $visit;
            }

            $em->flush();

            $this->updateVisits($this->collectedVisits);

            $this->collectedVisits = [];
            $em->clear();

            return true;
        }

        return false;
    }

    /**
     * Identify previous visits
     *
     * @param array $entities
     */
    protected function updateVisits($entities)
    {
        /** @var TrackingVisit $visit */
        foreach ($entities as $visit) {
            $this->logger->notice(
                sprintf(
                    'Process visit id: %s, visitorUid: %s',
                    $visit->getId(),
                    $visit->getVisitorUid()
                )
            );

            $identifier = $visit->getIdentifierTarget();
            if ($identifier) {
                $associationName = ExtendHelper::buildAssociationName(
                    ClassUtils::getClass($identifier),
                    IdentifierEventExtension::ASSOCIATION_KIND
                );

                $this->getEntityManager()
                    ->createQueryBuilder()
                    ->update(self::TRACKING_VISIT_ENTITY, 'entity')
                    ->set('entity.' . $associationName, ':identifier')
                    ->set('entity.identifierDetected', ':detected')
                    ->where('entity.visitorUid = :visitorUid')
                    ->andWhere('entity.firstActionTime < :maxDate')
                    ->andWhere('entity.identifierDetected = false OR entity.identifierDetected IS NULL')
                    ->andWhere('entity.parsedUID = 0')
                    ->andWhere('entity.trackingWebsite  = :website')
                    ->setParameter('visitorUid', $visit->getVisitorUid())
                    ->setParameter('maxDate', $visit->getFirstActionTime())
                    ->setParameter('website', $visit->getTrackingWebsite())
                    ->setParameter('identifier', $identifier)
                    ->setParameter('detected', true)
                    ->getQuery()
                    ->execute();
            }
        }
    }

    /**
     * Collect new tracking visits with tracking visit events
     */
    protected function processVisits()
    {
        $queryBuilder = $this->getEntityManager()
            ->getRepository(self::TACKING_EVENT_ENTITY)
            ->createQueryBuilder('entity')
            ->where('entity.parsed = false OR entity.parsed IS NULL')
            ->orderBy('entity.createdAt', 'ASC')
            ->setMaxResults(self::BATCH_SIZE);

        $entities = $queryBuilder->getQuery()->getResult();

        if ($entities) {
            $this->processTrackingVisits($entities);
            return true;
        }

        return false;
    }

    /**
     * @param array|TrackingEvent[] $entities
     */
    protected function processTrackingVisits($entities)
    {
        $this->logger->notice('Process batch START - '. date('Y-m-d H:i:s'));
        $em = $this->getEntityManager();

        /** @var  TrackingEvent $event */
        foreach ($entities as $event) {
            $this->logger->notice($event->getId());

            $trackingVisitEvent = new TrackingVisitEvent();

            $trackingVisitEvent->setEvent($this->getEventType($event));

            $eventData   = $event->getEventData();
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

        $this->updateVisits($this->collectedVisits);

        $this->collectedVisits = [];
        $this->eventDictionary = [];
        $em->clear();

        $this->logger->notice('Process batch END - ' . date('Y-m-d H:i:s'));
        $this->logger->notice(
            sprintf(
                'Memory usage (currently) %dMB/ (max) %dMB',
                round(memory_get_usage(true) / 1024 / 1024),
                memory_get_peak_usage(true) / 1024 / 1024)
        );
    }

    /**
     * @param TrackingEvent $trackingEvent
     * @param \stdClass     $decodedData
     *
     * @return TrackingVisit
     */
    protected function getTrackingVisit(TrackingEvent $trackingEvent, $decodedData)
    {
        $visitorUid     = $decodedData->_id;
        $userIdentifier = $trackingEvent->getUserIdentifier();

        $hash = md5($visitorUid . $userIdentifier);

        // try to find existing visit
        if (!empty($this->collectedVisits) && array_key_exists($hash, $this->collectedVisits)) {
            $visit = $this->collectedVisits[$hash];
        } else {
            $visit = $this->doctrine->getRepository(self::TRACKING_VISIT_ENTITY)->findOneBy(
                [
                    'visitorUid'      => $visitorUid,
                    'userIdentifier'  => $trackingEvent->getUserIdentifier(),
                    'trackingWebsite' => $trackingEvent->getWebsite()
                ]
            );
        }

        if (!$visit) {
            $visit = new TrackingVisit();
            $visit->setParsedUID(0);
            $visit->setParsingCount(0);
            $visit->setUserIdentifier($trackingEvent->getUserIdentifier());
            $visit->setVisitorUid($visitorUid);
            $visit->setFirstActionTime($trackingEvent->getCreatedAt());
            $visit->setLastActionTime($trackingEvent->getCreatedAt());
            $visit->setTrackingWebsite($trackingEvent->getWebsite());
            $visit->setIdentifierDetected(false);

            $this->identifyTrackingVisit($visit);

            $this->collectedVisits[$hash] = $visit;
        } else {
            if ($visit->getFirstActionTime() > $trackingEvent->getCreatedAt()) {
                $visit->setFirstActionTime($trackingEvent->getCreatedAt());
            }
            if ($visit->getLastActionTime() < $trackingEvent->getCreatedAt()) {
                $visit->setLastActionTime($trackingEvent->getCreatedAt());
            }
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
            $eventType = $this->getEntityManager()
                ->getRepository('OroTrackingBundle:TrackingEventDictionary')
                ->findOneBy(
                    [
                        'name'    => $event->getName(),
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
     */
    protected function identifyTrackingVisit(TrackingVisit $visit)
    {
        /**
         * try to identify visit
         */
        $idObj = $this->trackingIdentification->identify($visit);
        if ($idObj) {
            /**
             * if identification was successful we should:
             *  - assign visit to target
             *  - assign all previous visits to same identified object(s).
             */

            $this->logger->notice('-- ' . $idObj['parsedUID']);
            $this->logger->notice('-- ' . $idObj['targetObject']->getFirstName() . ' ' . $idObj['targetObject']->getLastName());

            if ($idObj['parsedUID'] !== null) {
                $visit->setParsedUID($idObj['parsedUID']);
                if ($idObj['targetObject']) {
                    $visit->setIdentifierTarget($idObj['targetObject']);
                    $visit->setIdentifierDetected(true);
                }
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
