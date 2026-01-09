<?php

namespace Oro\Bundle\ImapBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\DistributionBundle\Handler\ApplicationState;
use Oro\Bundle\ImapBundle\Async\Topic\ClearInactiveMailboxTopic;
use Oro\Component\DependencyInjection\ContainerAwareInterface;
use Oro\Component\DependencyInjection\ContainerAwareTrait;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

/**
 * Send to the message queue 'clear inactive mailboxes' if application is installed
 */
class ClearInactiveMailboxes extends AbstractFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    #[\Override]
    public function load(ObjectManager $manager)
    {
        if ($this->container->get(ApplicationState::class)->isInstalled()) {
            $this->getProducer()->send(ClearInactiveMailboxTopic::getName(), []);
        }
    }

    /**
     * @return MessageProducerInterface
     */
    private function getProducer()
    {
        return $this->container->get('oro_message_queue.client.message_producer');
    }
}
