<?php

namespace Oro\Bundle\IntegrationBundle\Async;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\IntegrationBundle\Async\Topic\SyncIntegrationTopic;
use Oro\Bundle\IntegrationBundle\Authentication\Token\IntegrationTokenAwareTrait;
use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
use Oro\Bundle\IntegrationBundle\Provider\LoggerStrategyAwareInterface;
use Oro\Bundle\IntegrationBundle\Provider\SyncProcessorRegistry;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Async processor to run integration processor
 */
class SyncIntegrationProcessor implements MessageProcessorInterface, ContainerAwareInterface, TopicSubscriberInterface
{
    use ContainerAwareTrait;
    use IntegrationTokenAwareTrait;

    /**
     * @var ManagerRegistry
     */
    private $doctrine;

    /**
     * @var SyncProcessorRegistry
     */
    private $syncProcessorRegistry;

    /**
     * @var JobRunner
     */
    private $jobRunner;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        ManagerRegistry $doctrine,
        TokenStorageInterface $tokenStorage,
        SyncProcessorRegistry $syncProcessorRegistry,
        JobRunner $jobRunner,
        LoggerInterface $logger
    ) {
        $this->doctrine = $doctrine;
        $this->tokenStorage = $tokenStorage;
        $this->syncProcessorRegistry = $syncProcessorRegistry;
        $this->jobRunner = $jobRunner;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [SyncIntegrationTopic::getName()];
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $messageBody = $message->getBody();

        /** @var EntityManagerInterface $em */
        $em = $this->doctrine->getManager();

        /** @var Integration $integration */
        $integration = $em->find(Integration::class, $messageBody['integration_id']);
        if (!$integration || !$integration->isEnabled()) {
            $this->logger->critical('Integration should exist and be enabled');

            return self::REJECT;
        }

        $em->getConnection()->getConfiguration()->setSQLLogger(null);

        $this->setTemporaryIntegrationToken($integration);
        $integration->getTransport()->getSettingsBag()->set('page_size', $messageBody['transport_batch_size']);

        $result = $this->jobRunner->runUniqueByMessage(
            $message,
            function () use ($integration, $messageBody) {
                $processor = $this->syncProcessorRegistry->getProcessorForIntegration($integration);
                if ($processor instanceof LoggerStrategyAwareInterface) {
                    $processor->getLoggerStrategy()->setLogger($this->logger);
                }

                return $processor->process(
                    $integration,
                    $messageBody['connector'],
                    $messageBody['connector_parameters']
                );
            }
        );

        return $result ? self::ACK : self::REJECT;
    }
}
