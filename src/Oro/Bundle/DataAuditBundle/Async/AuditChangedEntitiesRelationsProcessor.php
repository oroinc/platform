<?php
namespace Oro\Bundle\DataAuditBundle\Async;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\DataAuditBundle\Model\EntityReference;
use Oro\Bundle\DataAuditBundle\Service\ConvertEntityChangesToAuditService;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;

class AuditChangedEntitiesRelationsProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    /** @var ManagerRegistry */
    private $doctrine;

    /** @var ConvertEntityChangesToAuditService */
    private $entityChangesToAuditEntryConverter;

    /**
     * @param ManagerRegistry                    $doctrine
     * @param ConvertEntityChangesToAuditService $entityChangesToAuditEntryConverter
     */
    public function __construct(
        ManagerRegistry $doctrine,
        ConvertEntityChangesToAuditService $entityChangesToAuditEntryConverter
    ) {
        $this->doctrine = $doctrine;
        $this->entityChangesToAuditEntryConverter = $entityChangesToAuditEntryConverter;
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

        $this->entityChangesToAuditEntryConverter->convert(
            $body['collections_updated'],
            $transactionId,
            $loggedAt,
            $user,
            $organization
        );
        
        return self::ACK;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::ENTITIES_RELATIONS_CHANGED];
    }
}
