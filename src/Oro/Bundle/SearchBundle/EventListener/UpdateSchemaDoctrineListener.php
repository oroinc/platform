<?php

namespace Oro\Bundle\SearchBundle\EventListener;

use Doctrine\Bundle\DoctrineBundle\Command\Proxy\UpdateSchemaDoctrineCommand;
use Doctrine\Common\Persistence\ManagerRegistry;

use JMS\JobQueueBundle\Entity\Job;

use Symfony\Component\Console\Event\ConsoleExceptionEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;

use Oro\Bundle\EntityExtendBundle\Command\UpdateSchemaCommand;
use Oro\Bundle\SearchBundle\Command\ReindexCommand;
use Oro\Bundle\SearchBundle\Engine\FulltextIndexManager;

class UpdateSchemaDoctrineListener
{
    /** @var FulltextIndexManager */
    protected $fulltextIndexManager;

    /**  @var ManagerRegistry */
    protected $registry;

    /** @var bool|null */
    protected $isExceptionOccurred;

    /**
     * @param FulltextIndexManager $fulltextIndexManager
     * @param ManagerRegistry      $registry
     */
    public function __construct(FulltextIndexManager $fulltextIndexManager, ManagerRegistry $registry)
    {
        $this->fulltextIndexManager = $fulltextIndexManager;
        $this->registry = $registry;
    }

    /**
     * @param ConsoleTerminateEvent $event
     */
    public function onConsoleTerminate(ConsoleTerminateEvent $event)
    {
        if (!$this->isExceptionOccurred) {
            if ($event->getCommand() instanceof UpdateSchemaDoctrineCommand) {
                $output = $event->getOutput();
                $input  = $event->getInput();

                if ($input->getOption('force')) {
                    $result = $this->fulltextIndexManager->createIndexes();

                    $output->writeln('Schema update and create index completed.');
                    if ($result) {
                        $output->writeln('Indexes were created.');
                    }
                }
            }

            if ($event->getCommand() instanceof UpdateSchemaCommand) {
                $entities = $this->registry->getRepository('OroSearchBundle:UpdateEntity')->findAll();
                if (count($entities)) {
                    $em = $this->registry->getManager();
                    foreach ($entities as $entity) {
                        $job = new Job(ReindexCommand::COMMAND_NAME, ['class' => $entity->getEntity()]);
                        $em->persist($job);
                        $em->remove($entity);
                    }
                    $em->flush($job);
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
