<?php

namespace Oro\Bundle\TrackingBundle\Command;

use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\TrackingBundle\Entity\TrackingVisit;
use Oro\Bundle\TrackingBundle\Entity\TrackingVisitEvent;
use Oro\Component\Log\OutputLogger;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

use Oro\Bundle\TrackingBundle\Entity\TrackingEvent;

use Doctrine\Bundle\DoctrineBundle\Registry;

class TrackCommand extends ContainerAwareCommand
{
    /** @var Registry */
    protected $doctrine;
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('oro:tracking:parse')
            ->setDescription('Import tracking logs');
    }

    /**
     * {@internaldoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $processor = $this->getContainer()->get('oro_tracking.processor.tracking_processor');
        $processor->setLogger(new OutputLogger($output));
        $processor->process();
    }

    protected function getMapping()
    {
        return [
            'visit' => TrackingVisitEvent::EV_VISTIT,
            'order successfully placed' => TrackingVisitEvent::EV_ORDER,
            'cart item added' => TrackingVisitEvent::EV_ADD_CART,
            'user entered checkout' => TrackingVisitEvent::EV_CHECKOUT,
            'registration' => TrackingVisitEvent::EV_REGISTER,
        ];
    }


    /**
     * @param TrackingEvent $trackingEvent
     * @param               $coockieId
     * @param Registry      $doctrine
     * @return TrackingVisit
     */
    protected function getTrackingVisit(TrackingEvent $trackingEvent, $unserializedData)
    {
        $coockieId = $unserializedData->_id;
        $visit = $this->doctrine->getRepository('OroTrackingBundle:TrackingVisit')->findOneBy(
            [
                'visitor' => $coockieId,
                'userIdentifier' => $trackingEvent->getUserIdentifier()
            ]
        );
        if (!$visit) {
            $visit = new TrackingVisit();
            $visit->setParsedUID(-1);
            $visit->setUserIdentifier($trackingEvent->getUserIdentifier());
            $visit->setVisitor($coockieId);
            $visit->setFirstActionTime($trackingEvent->getCreatedAt());
            $visit->setLastActionTime($trackingEvent->getCreatedAt());


            $this->identifyCustomer($visit, $trackingEvent, $unserializedData);

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

    protected function identifyCustomer(TrackingVisit $visit, TrackingEvent $event, $unserializedData)
    {
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

            $customer = $this->doctrine->getRepository('OroCRMMagentoBundle:Customer')->findOneBy(['originId' => $customerId]);
            if ($customer) {
                $visit->setCustomer($customer);
                $this->identifyPrevVisits($visit->getVisitor(), $visit->getFirstActionTime(), $customer);
            }
        }
    }

    protected function identifyPrevVisits($visitor, $maxDate, $customer)
    {
        /** @var QueryBuilder $qb */
        $qb = $this->doctrine->getManager()->createQueryBuilder();
        $qb->select('entity');
        $visitors = $qb->from('OroTrackingBundle:TrackingVisit', 'entity')
            ->where('entity.visitor = :visitor')
            ->andWhere('entity.firstActionTime < :maxDate')
            ->andWhere('entity.customer is null')
            ->setParameter('visitor', $visitor)
            ->setParameter('maxDate', $maxDate)
            ->getQuery()
            ->getResult();

        if (!empty($visitors)) {
            /** @var TrackingVisit $visitorObject */
            foreach ($visitors as $visitorObject) {
                $visitorObject->setCustomer($customer);
                $this->doctrine->getManager()->persist($visitorObject);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function __execute(InputInterface $input, OutputInterface $output)
    {
        $start = time();

        $this->doctrine = $this->getContainer()->get('doctrine');

        $events = $this->doctrine->getRepository('OroTrackingBundle:TrackingEvent')->findBy([], ['createdAt' => 'ASC'], 2000, 18000);

        /** @var  \Oro\Bundle\TrackingBundle\Entity\TrackingEvent $event */
        foreach ($events as $event) {
            $output->writeln('<info>Parse ' . $event->getId(). '</info>');

            $trackingVisitEvent = new TrackingVisitEvent();
            $trackingVisitEvent->setEvent($this->getMapping()[$event->getName()]);

            $eventData = $event->getEventData();
            $unserializedData = json_decode($eventData->getData());

            $trackingVisit = $this->getTrackingVisit($event, $unserializedData);
            $trackingVisitEvent->setVisit($trackingVisit);
            $trackingVisitEvent->setWebEvent($event);

            $event->setParsed(true);

            $em = $this->doctrine->getManager();
            $em->persist($event);
            $em->persist($trackingVisitEvent);
            $em->persist($trackingVisit);

            $em->flush();
        }

        $output->writeln('<info>Parse time -> ' . (time()-$start). '</info>');
        $output->writeln('<info>Memory usage -> ' . memory_get_peak_usage(). '</info>');
    }
}
