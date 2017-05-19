<?php

namespace Oro\Bundle\EmailBundle\EventListener;

use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Bundle\EmailBundle\Async\Topics as EmailTopics;
use Oro\Bundle\MigrationBundle\Event\MigrationDataFixturesEvent;
use Oro\Bundle\PlatformBundle\Manager\OptionalListenerManager;

/**
 * Disables updating email associations during loading of demo data
 * and triggers it after demo data are loaded.
 */
class EmailAssociationsDemoDataFixturesListener
{
    /**
     * This listener is disabled to prevent a lot of UpdateEmailAssociations messages
     */
    const ENTITY_LISTENER = 'oro_email.listener.entity_listener';

    /** @var OptionalListenerManager */
    private $listenerManager;

    /** @var MessageProducerInterface */
    private $messageProducer;

    /**
     * @param OptionalListenerManager  $listenerManager
     * @param MessageProducerInterface $messageProducer
     */
    public function __construct(
        OptionalListenerManager $listenerManager,
        MessageProducerInterface $messageProducer
    ) {
        $this->listenerManager = $listenerManager;
        $this->messageProducer = $messageProducer;
    }

    /**
     * @param MigrationDataFixturesEvent $event
     */
    public function onPreLoad(MigrationDataFixturesEvent $event)
    {
        if ($event->isDemoFixtures()) {
            $this->listenerManager->disableListener(self::ENTITY_LISTENER);
        }
    }

    /**
     * @param MigrationDataFixturesEvent $event
     */
    public function onPostLoad(MigrationDataFixturesEvent $event)
    {
        if ($event->isDemoFixtures()) {
            $this->listenerManager->enableListener(self::ENTITY_LISTENER);

            $this->messageProducer->send(EmailTopics::UPDATE_ASSOCIATIONS_TO_EMAILS, []);
        }
    }
}
