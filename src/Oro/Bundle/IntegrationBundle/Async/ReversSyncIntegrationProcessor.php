<?php
namespace Oro\Bundle\IntegrationBundle\Async;

use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
use Oro\Bundle\IntegrationBundle\Exception\LogicException;
use Oro\Bundle\IntegrationBundle\Manager\TypesRegistry;
use Oro\Bundle\IntegrationBundle\Provider\ReverseSyncProcessor;
use Oro\Bundle\IntegrationBundle\Provider\TwoWaySyncConnectorInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class ReversSyncIntegrationProcessor implements
    MessageProcessorInterface,
    ContainerAwareInterface,
    TopicSubscriberInterface
{
    use ContainerAwareTrait;

    /**
     * @var RegistryInterface
     */
    private $doctrine;
    
    /**
     * @var ReverseSyncProcessor
     */
    private $reverseSyncProcessor;

    /**
     * @var TypesRegistry
     */
    private $typesRegistry;

    /**
     * @param RegistryInterface $doctrine
     * @param ReverseSyncProcessor $reverseSyncProcessor
     * @param TypesRegistry $typesRegistry
     */
    public function __construct(
        RegistryInterface $doctrine,
        ReverseSyncProcessor $reverseSyncProcessor,
        TypesRegistry $typesRegistry
    ) {
        $this->doctrine = $doctrine;
        $this->reverseSyncProcessor = $reverseSyncProcessor;
        $this->typesRegistry = $typesRegistry;
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
        // TODO CRM-5838 unique job

        $body = JSON::decode($message->getBody());
        $body = array_replace_recursive([
            'integrationId' => null,
            'connector' => null,
            'connector_parameters' => [],
            'transport_batch_size' => 100,
        ], $body);

        if (false == $body['integrationId']) {
            throw new \LogicException('The message invalid. It must have integrationId set');
        }

        $em = $this->doctrine->getManager();
        
        /** @var Integration $integration */
        $integration = $em->find(Integration::class, $body['integrationId']);
        if (false == $integration) {
            return self::REJECT;
        }
        if (false == $integration->isEnabled()) {
            return self::REJECT;
        }

        $connector = $this->typesRegistry->getConnectorType($integration->getType(), $body['connector']);
        if (!$connector instanceof TwoWaySyncConnectorInterface) {
            throw new LogicException(sprintf('Unable to schedule job for "%s" connector type', $body['connector']));
        }

        $this->reverseSyncProcessor->process(
            $integration,
            $body['connector'],
            $body['connector_parameters']
        );

        return self::ACK;
    }
}
