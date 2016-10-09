<?php
namespace Oro\Bundle\DataAuditBundle\Async;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\DataAuditBundle\Service\ConvertEntityChangesToAuditService;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\AbstractUser;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;

class AuditChangedEntitiesRelationsProcessor implements MessageProcessorInterface, TopicSubscriberInterface
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
     * @param ManagerRegistry $doctrine
     * @param ConvertEntityChangesToAuditService $convertEntityChangesToAuditService
     */
    public function __construct(
        ManagerRegistry $doctrine,
        ConvertEntityChangesToAuditService $convertEntityChangesToAuditService
    ) {
        $this->doctrine = $doctrine;
        $this->convertEntityChangesToAuditService = $convertEntityChangesToAuditService;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $body = JSON::decode($message->getBody());

        $loggedAt = \DateTime::createFromFormat('U', $body['timestamp']);
        $transactionId = $body['transaction_id'];

        /** @var AbstractUser|null $user */
        $user = null;
        if (isset($body['user_id'])) {
            $user = $this->doctrine->getRepository($body['user_class'])->find($body['user_id']);
        }

        /** @var Organization|null $organization */
        $organization = null;
        if (isset($body['organization_id'])) {
            $organization = $this->doctrine->getRepository(Organization::class)->find($body['organization_id']);
        }

        $this->convertEntityChangesToAuditService->convert(
            $body['collections_updated'],
            $transactionId,
            $loggedAt,
            null,
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
