<?php

namespace Oro\Bundle\SearchBundle\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;

use JMS\JobQueueBundle\Entity\Job;

use Symfony\Component\Console\Event\ConsoleExceptionEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;

use Oro\Bundle\EntityExtendBundle\Command\UpdateSchemaCommand;
use Oro\Bundle\SearchBundle\Command\ReindexCommand;

class UpdateSchemaDoctrineListener
{
    /**  @var ManagerRegistry */
    protected $registry;

    /** @var bool|null */
    protected $isExceptionOccurred;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param ConsoleTerminateEvent $event
     */
    public function onConsoleTerminate(ConsoleTerminateEvent $event)
    {
        if (!$this->isExceptionOccurred) {
            if ($event->getCommand() instanceof UpdateSchemaCommand) {
                /** @var EntityManager $searchEntityManager */
                $searchEntityManager = $this->registry->getManagerForClass('OroSearchBundle:UpdateEntity');
                $entities = $searchEntityManager->getRepository('OroSearchBundle:UpdateEntity')->findAll();
                if (count($entities)) {
                    /** @var EntityManager $em */
                    $em = $this->registry->getManagerForClass('JMS\JobQueueBundle\Entity\Job');
                    foreach ($entities as $entity) {
                        $job = new Job(ReindexCommand::COMMAND_NAME, ['class' => $entity->getEntity()]);
                        $em->persist($job);
                        $searchEntityManager->remove($entity);
                    }
                    $em->flush();
                    $searchEntityManager->flush();
                }
            }
        }
        $this->isExceptionOccurred = null;
    }

    /**
     * @param ConsoleExceptionEvent $event
     */
    public function onConsoleException(ConsoleExceptionEvent $event)
    {
        $this->isExceptionOccurred = true;
    }
}
