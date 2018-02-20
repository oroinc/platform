<?php

namespace Oro\Bundle\EmailBundle\EventListener;

use Doctrine\ORM\Event\PostFlushEventArgs;
use Oro\Bundle\EmailBundle\Async\Topics;
use Oro\Bundle\EmailBundle\Manager\AutoResponseManager;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureToggleableInterface;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class AutoResponseListener extends MailboxEmailListener implements FeatureToggleableInterface
{
    use FeatureCheckerHolderTrait;

    /** @var ContainerInterface */
    private $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param PostFlushEventArgs $args
     */
    public function postFlush(PostFlushEventArgs $args)
    {
        if (!$this->isFeaturesEnabled()) {
            return;
        }

        $emailIds = $this->popEmailIds();
        if (!$emailIds) {
            return;
        }
        
        $this->getProducer()->send(Topics::SEND_AUTO_RESPONSES, ['ids' => $emailIds]);
    }

    /**
     * @return array
     */
    protected function popEmailIds()
    {
        $emailIds = [];
        if (!empty($this->emailBodies)) {
            $autoResponseManager = $this->getAutoResponseManager();
            foreach ($this->emailBodies as $emailBody) {
                $email = $emailBody->getEmail();
                if ($autoResponseManager->hasAutoResponses($email)) {
                    $emailIds[] = $email->getId();
                }
            }
            $this->emailBodies = [];
        }

        return $emailIds;
    }

    /**
     * @return AutoResponseManager
     */
    protected function getAutoResponseManager()
    {
        return $this->container->get('oro_email.autoresponserule_manager');
    }

    /**
     * @return MessageProducerInterface
     */
    protected function getProducer()
    {
        return $this->container->get('oro_message_queue.client.message_producer');
    }
}
