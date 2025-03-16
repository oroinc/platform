<?php

namespace Oro\Bundle\EmailBundle\EventListener;

use Doctrine\ORM\Event\PostFlushEventArgs;
use Oro\Bundle\EmailBundle\Async\Topic\SendAutoResponsesTopic;
use Oro\Bundle\EmailBundle\Manager\AutoResponseManager;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureToggleableInterface;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

/**
 * Used to send auto response for multiple emails
 */
class AutoResponseListener extends MailboxEmailListener implements
    FeatureToggleableInterface,
    ServiceSubscriberInterface
{
    use FeatureCheckerHolderTrait;

    public function __construct(
        private ContainerInterface $container
    ) {
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [
            AutoResponseManager::class,
            MessageProducerInterface::class
        ];
    }

    public function postFlush(PostFlushEventArgs $args): void
    {
        if (!$this->isFeaturesEnabled()) {
            return;
        }

        $emailIds = $this->popEmailIds();
        if (!$emailIds) {
            return;
        }

        /** @var MessageProducerInterface $producer */
        $producer = $this->container->get(MessageProducerInterface::class);
        $producer->send(SendAutoResponsesTopic::getName(), ['ids' => $emailIds]);
    }

    protected function popEmailIds(): array
    {
        $emailIds = [];
        if (!empty($this->emailBodies)) {
            /** @var AutoResponseManager $autoResponseManager */
            $autoResponseManager = $this->container->get(AutoResponseManager::class);
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
}
