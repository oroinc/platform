<?php
namespace Oro\Bundle\IntegrationBundle\Async;

use Doctrine\ORM\EntityManagerInterface;

use Psr\Log\LoggerInterface;

use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
use Oro\Bundle\IntegrationBundle\Provider\SyncProcessorRegistry;
use Oro\Bundle\SecurityBundle\Authentication\Token\ConsoleToken;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;

class SyncIntegrationProcessor implements MessageProcessorInterface, ContainerAwareInterface, TopicSubscriberInterface
{
    use ContainerAwareTrait;

    /**
     * @var RegistryInterface
     */
    private $doctrine;
    
    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;
    
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
            $this->logger->critical('Invalid message: integration_id is empty', ['message' => $message]);
            return self::REJECT;
        }

        /** @var EntityManagerInterface $em */
        $em = $this->doctrine->getManager();

        /** @var Integration $integration */
        $integration = $em->find(Integration::class, $body['integration_id']);
        if (! $integration) {
            $this->logger->critical(
                sprintf('Integration not found: %s', $body['integration_id']),
                ['message' => $message]
            );
            return self::REJECT;
        }

        if (! $integration->isEnabled()) {
            $this->logger->critical(
                sprintf('Integration id not enabled: %s', $body['integration_id']),
                ['message' => $message]
            );
            return self::REJECT;
        }

        $jobName = 'oro_integration:sync_integration:'.$body['integration_id'];
        $ownerId = $message->getMessageId();

        if (! $ownerId) {
            $this->logger->critical(
                'Internal error: ownerid is empty',
                ['message' => $message]
            );
            return self::REJECT;
        }

        $em->getConnection()->getConfiguration()->setSQLLogger(null);

        $this->updateToken($integration);
        $integration->getTransport()->getSettingsBag()->set('page_size', $body['transport_batch_size']);

        $result = $this->jobRunner->runUnique($ownerId, $jobName, function () use ($integration, $body) {

            $processor = $this->syncProcessorRegistry->getProcessorForIntegration($integration);

            return $processor->process(
                $integration,
                $body['connector'],
                $body['connector_parameters']
            );
        });

        return $result ? self::ACK : self::REJECT;
    }

    /**
     * @param Integration $integration
     */
    protected function updateToken(Integration $integration)
    {
        $token = $this->tokenStorage->getToken();
        if (false == $token) {
            $token = new ConsoleToken();
            $this->tokenStorage->setToken($token);
        }

        $token->setOrganizationContext($integration->getOrganization());
    }
}
