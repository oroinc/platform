<?php

namespace Oro\Bundle\IntegrationBundle\Async;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\IntegrationBundle\Authentication\Token\IntegrationTokenAwareTrait;
use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
use Oro\Bundle\IntegrationBundle\Provider\LoggerStrategyAwareInterface;
use Oro\Bundle\IntegrationBundle\Provider\SyncProcessorRegistry;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;
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
     * @var RegistryInterface
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

    /**
     * @param RegistryInterface $doctrine
     * @param TokenStorageInterface $tokenStorage
     * @param SyncProcessorRegistry $syncProcessorRegistry
     * @param JobRunner $jobRunner
     * @param LoggerInterface $logger;
     */
    public function __construct(
        RegistryInterface $doctrine,
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
        return [Topics::SYNC_INTEGRATION];
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $body = JSON::decode($message->getBody());
        $body = array_replace_recursive([
            'integration_id' => null,
            'connector' => null,
            'connector_parameters' => [],
            'transport_batch_size' => 100,
        ], $body);

        if (! $body['integration_id']) {
            $this->logger->critical('Invalid message: integration_id is empty');

            return self::REJECT;
        }

        /** @var EntityManagerInterface $em */
        $em = $this->doctrine->getManager();

        /** @var Integration $integration */
        $integration = $em->find(Integration::class, $body['integration_id']);
        if (! $integration) {
            $this->logger->error(sprintf('Integration with id "%s" is not found', $body['integration_id']));

            return self::REJECT;
        }

        if (! $integration->isEnabled()) {
            $this->logger->error(sprintf('Integration with id "%s" is not enabled', $body['integration_id']));

            return self::REJECT;
        }

        $jobName = 'oro_integration:sync_integration:'.$body['integration_id'];
        $ownerId = $message->getMessageId();

        if (! $ownerId) {
            $this->logger->critical('Internal error: ownerId is empty');

            return self::REJECT;
        }

        $em->getConnection()->getConfiguration()->setSQLLogger(null);

        $this->setTemporaryIntegrationToken($integration);
        $integration->getTransport()->getSettingsBag()->set('page_size', $body['transport_batch_size']);

        $result = $this->jobRunner->runUnique($ownerId, $jobName, function () use ($integration, $body) {
            $processor = $this->syncProcessorRegistry->getProcessorForIntegration($integration);
            if ($processor instanceof LoggerStrategyAwareInterface) {
                $processor->getLoggerStrategy()->setLogger($this->logger);
            }

            return $processor->process(
                $integration,
                $body['connector'],
                $body['connector_parameters']
            );
        });

        return $result ? self::ACK : self::REJECT;
    }
}
