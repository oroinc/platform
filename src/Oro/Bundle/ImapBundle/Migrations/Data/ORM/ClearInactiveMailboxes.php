<?php

namespace Oro\Bundle\ImapBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\ImapBundle\Async\Topics;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class ClearInactiveMailboxes extends AbstractFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        if ($this->container->hasParameter('installed') && $this->container->getParameter('installed')) {
            $this->getProducer()->send(Topics::CLEAR_INACTIVE_MAILBOX, []);
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
