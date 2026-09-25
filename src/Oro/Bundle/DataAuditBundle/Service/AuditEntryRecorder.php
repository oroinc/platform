<?php

namespace Oro\Bundle\DataAuditBundle\Service;

use Oro\Bundle\DataAuditBundle\Async\Topic\AuditEntryTopic;
use Oro\Bundle\DataAuditBundle\Model\AuditEntry;
use Oro\Bundle\DataAuditBundle\Provider\AuditMessageBodyProvider;
use Oro\Bundle\DistributionBundle\Handler\ApplicationState;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Records a Data Audit entry built by a bundle: who is acting, whether anything is audited at all and how
 * the entry reaches the database is decided here, so a producer only describes what changed.
 */
class AuditEntryRecorder
{
    private const string FEATURE_NAME = 'data_audit';

    public function __construct(
        private readonly MessageProducerInterface $messageProducer,
        private readonly TokenStorageInterface $tokenStorage,
        private readonly AuditMessageBodyProvider $messageBodyProvider,
        private readonly FeatureChecker $featureChecker,
        private readonly ApplicationState $applicationState
    ) {
    }

    public function isEnabled(): bool
    {
        return
            $this->applicationState->isInstalled()
            && $this->featureChecker->isFeatureEnabled(self::FEATURE_NAME)
            && null !== $this->tokenStorage->getToken();
    }

    public function record(AuditEntry $entry, ?string $transactionId = null): void
    {
        if (!$entry->hasChanges() || !$this->isEnabled()) {
            return;
        }

        $this->messageProducer->send(AuditEntryTopic::getName(), array_merge(
            [
                'timestamp' => time(),
                'transaction_id' => $transactionId ?? UUIDGenerator::v4(),
                'object_class' => $entry->getObjectClass(),
                'object_id' => $entry->getObjectId(),
                'object_name' => $entry->getObjectName(),
                'action' => $entry->getAction(),
                'changes' => $entry->getChanges(),
            ],
            $this->messageBodyProvider->prepareAuthorData($this->tokenStorage->getToken())
        ));
    }
}
