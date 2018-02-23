<?php

namespace Oro\Bundle\MigrationBundle\Migration;

use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\MigrationBundle\Event\MigrationDataFixturesEvent;
use Oro\Bundle\MigrationBundle\Event\MigrationEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Executes ORM data fixtures.
 */
class DataFixturesExecutor implements DataFixturesExecutorInterface
{
    /** @var EntityManager */
    private $em;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /** @var callable|null */
    private $logger;

    /**
     * @param EntityManager            $em
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(EntityManager $em, EventDispatcherInterface $eventDispatcher)
    {
        $this->em = $em;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(array $fixtures, $fixturesType)
    {
        $event = new MigrationDataFixturesEvent($this->em, $fixturesType, $this->logger);
        $this->eventDispatcher->dispatch(MigrationEvents::DATA_FIXTURES_PRE_LOAD, $event);

        $executor = new ORMExecutor($this->em);
        if (null !== $this->logger) {
            $executor->setLogger($this->logger);
        }
        $executor->execute($fixtures, true);

        $this->eventDispatcher->dispatch(MigrationEvents::DATA_FIXTURES_POST_LOAD, $event);
    }

    /**
     * {@inheritdoc}
     */
    public function setLogger($logger)
    {
        $this->logger = $logger;
    }
}
