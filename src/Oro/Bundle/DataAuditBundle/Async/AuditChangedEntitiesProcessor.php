<?php
namespace Oro\Bundle\DataAuditBundle\Async;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\DataAuditBundle\Entity\Audit;
use Oro\Bundle\DataAuditBundle\Model\EntityReference;
use Oro\Bundle\DataAuditBundle\Service\ConvertEntityChangesToAuditService;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Component\MessageQueue\Client\Message;
use Oro\Component\MessageQueue\Client\MessagePriority;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;

class AuditChangedEntitiesProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    /**
     * @var ManagerRegistry
     */
    private $doctrine;

    /**
     * @var ConvertEntityChangesToAuditService
     */
    private $convertEntityChangesToAuditService;

    /**
     * @var MessageProducerInterface
     */
    private $messageProducer;

    /**
     * @param ManagerRegistry $doctrine
     * @param ConvertEntityChangesToAuditService $convertEntityChangesToAuditService
     * @param MessageProducerInterface $messageProducer
     */
    public function __construct(
        ManagerRegistry $doctrine,
        ConvertEntityChangesToAuditService $convertEntityChangesToAuditService,
        MessageProducerInterface $messageProducer
    ) {
        $this->doctrine = $doctrine;
        $this->convertEntityChangesToAuditService = $convertEntityChangesToAuditService;
        $this->messageProducer = $messageProducer;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $body = JSON::decode($message->getBody());

        $loggedAt = \DateTime::createFromFormat('U', $body['timestamp']);
        $transactionId = $body['transaction_id'];

        $user = new EntityReference();
        if (isset($body['user_id'])) {
            $user = new EntityReference($body['user_class'], $body['user_id']);
        }

        $organization = new EntityReference();
        if (isset($body['organization_id'])) {
            $organization = new EntityReference(Organization::class, $body['organization_id']);
        }

        $this->convertEntityChangesToAuditService->convert(
            $body['entities_inserted'],
            $transactionId,
            $loggedAt,
            $user,
            $organization,
            Audit::ACTION_CREATE
        );

        $this->convertEntityChangesToAuditService->convert(
            $body['entities_updated'],
            $transactionId,
            $loggedAt,
            $user,
            $organization,
            Audit::ACTION_UPDATE
        );

        $this->convertEntityChangesToAuditService->convertSkipFields(
            $body['entities_deleted'],
            $transactionId,
            $loggedAt,
            $user,
            $organization,
            Audit::ACTION_REMOVE
        );

        $message = new Message();
        $message->setPriority(MessagePriority::VERY_LOW);
        $message->setBody($body);

        $this->messageProducer->send(Topics::ENTITIES_RELATIONS_CHANGED, $message);
        $this->messageProducer->send(Topics::ENTITIES_INVERSED_RELATIONS_CHANGED, $message);

        return self::ACK;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::ENTITIES_CHANGED];
    }
}
