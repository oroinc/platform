<?php
namespace Oro\Bundle\IntegrationBundle\Async;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
use Oro\Bundle\IntegrationBundle\Provider\SyncProcessorRegistry;
use Oro\Bundle\SecurityBundle\Authentication\Token\ConsoleToken;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

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
     * @param RegistryInterface $doctrine
     * @param TokenStorageInterface $tokenStorage
     * @param SyncProcessorRegistry $syncProcessorRegistry
     * @param JobRunner $jobRunner
     */
    public function __construct(
        RegistryInterface $doctrine,
        TokenStorageInterface $tokenStorage,
        SyncProcessorRegistry $syncProcessorRegistry,
        JobRunner $jobRunner
    ) {
        $this->doctrine = $doctrine;
        $this->tokenStorage = $tokenStorage;
        $this->syncProcessorRegistry = $syncProcessorRegistry;
        $this->jobRunner = $jobRunner;
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

        if (false == $body['integration_id']) {
            throw new \LogicException('The message invalid. It must have integration_id set');
        }

        $jobName = 'oro_integration:sync_integration:'.$body['integration_id'];
        $ownerId = $message->getMessageId();

        $result = $this->jobRunner->runUnique($ownerId, $jobName, function () use ($body) {
            /** @var EntityManagerInterface $em */
            $em = $this->doctrine->getManager();

            /** @var Integration $integration */
            $integration = $em->find(Integration::class, $body['integration_id']);
            if (false == $integration) {
                return false;
            }
            if (false == $integration->isEnabled()) {
                return false;
            }

            $em->getConnection()->getConfiguration()->setSQLLogger(null);

            $this->updateToken($integration);
            $integration->getTransport()->getSettingsBag()->set('page_size', $body['transport_batch_size']);

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
