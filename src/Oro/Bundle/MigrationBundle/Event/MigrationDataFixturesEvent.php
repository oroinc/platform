<?php

namespace Oro\Bundle\MigrationBundle\Event;

use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\MigrationBundle\Migration\DataFixturesExecutorInterface;
use Symfony\Component\EventDispatcher\Event;

class MigrationDataFixturesEvent extends Event
{
    /** @var ObjectManager */
    private $manager;

    /** @var string */
    private $fixturesType;

    /** @var callable|null */
    private $logger;

    /**
     * @param ObjectManager $manager      The entity manager
     * @param string        $fixturesType The type of data fixtures
     * @param callable|null $logger       The callback for logging messages
     */
    public function __construct(ObjectManager $manager, $fixturesType, $logger = null)
    {
        $this->manager = $manager;
        $this->fixturesType = $fixturesType;
        $this->logger = $logger;
    }

    /**
     * Gets the entity manager.
     *
     * @return ObjectManager
     */
    public function getObjectManager()
    {
        return $this->manager;
    }

    /**
     * Gets the type of data fixtures.
     *
     * @return string
     */
    public function getFixturesType()
    {
        return $this->fixturesType;
    }

    /**
     * Adds a message to the logger.
     *
     * @param string $message
     */
    public function log($message)
    {
        if (null !== $this->logger) {
            $logger = $this->logger;
            $logger($message);
        }
    }

    /**
     * Checks whether this event is raised for data fixtures contain the main data for an application.
     *
     * @return bool
     */
    public function isMainFixtures()
    {
        return DataFixturesExecutorInterface::MAIN_FIXTURES === $this->getFixturesType();
    }

    /**
     * Checks whether this event is raised for data fixtures contain the demo data for an application.
     *
     * @return bool
     */
    public function isDemoFixtures()
    {
        return DataFixturesExecutorInterface::DEMO_FIXTURES === $this->getFixturesType();
    }
}
