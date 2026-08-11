<?php

namespace Oro\Bundle\DataAuditBundle\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Oro\Bundle\DataAuditBundle\Async\Topic\ConfigChangeAuditTopic;
use Oro\Bundle\DataAuditBundle\Entity\Audit;
use Oro\Bundle\DataAuditBundle\Model\ConfigAuditValueNormalizer;
use Oro\Bundle\DataAuditBundle\Provider\AuditMessageBodyProvider;
use Oro\Bundle\DataAuditBundle\Provider\ConfigAuditLevelProvider;
use Oro\Bundle\DistributionBundle\Handler\ApplicationState;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Records every system configuration change (oro_config.update_after) as a first-class Data Audit entry
 * with who / when / old / new.
 *
 * The configuration level becomes the audit Entity Type (object class) — see {@see ConfigAuditLevelProvider} — so
 * that every level (System / Organization / Website / Customer / ...) is a distinct, filterable type; the
 * object id is the plain scope id (0 for global). A setting is recorded by its stable configuration key,
 * which is resolved to a breadcrumb at display time (see
 * {@see \Oro\Bundle\DataAuditBundle\Provider\ConfigAuditFieldLabelProvider}), and the action comes
 * straight from the configuration change set.
 *
 * The entry is published to a dedicated message queue topic and written by
 * {@see \Oro\Bundle\DataAuditBundle\Async\ConfigChangeAuditProcessor}; everything that needs the security
 * token or the configuration definition is resolved here, at change time.
 */
class ConfigChangeAuditListener
{
    private const string FEATURE_NAME = 'data_audit';

    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly TokenStorageInterface $tokenStorage,
        private readonly EntityNameResolver $entityNameResolver,
        private readonly FeatureChecker $featureChecker,
        private readonly MessageProducerInterface $messageProducer,
        private readonly ApplicationState $applicationState,
        private readonly ConfigAuditValueNormalizer $valueNormalizer,
        private readonly AuditMessageBodyProvider $messageBodyProvider,
        private readonly ConfigAuditLevelProvider $levelProvider
    ) {
    }

    public function onConfigUpdate(ConfigUpdateEvent $event): void
    {
        $changeSet = $event->getChangeSet() + $event->getUseParentScopeChanges();
        if (!$changeSet || !$this->isAuditable()) {
            return;
        }

        $actions = [];
        $changes = [];
        foreach ($changeSet as $name => $change) {
            $action = $change['action'] ?? Audit::ACTION_UPDATE;
            $actions[] = $action;
            $changes[$name] = ['field' => $name] + $this->valueNormalizer->normalize(
                $name,
                Audit::ACTION_CREATE === $action ? null : ($change['old'] ?? null),
                Audit::ACTION_REMOVE === $action ? null : ($change['new'] ?? null)
            );
        }

        $scope = $event->getScope();
        $scopeId = $event->getScopeId();

        $this->messageProducer->send(ConfigChangeAuditTopic::getName(), array_merge(
            [
                'timestamp' => time(),
                'transaction_id' => UUIDGenerator::v4(),
                'object_class' => $this->levelProvider->getClassForScope($scope),
                'object_id' => (string)$scopeId,
                'object_name' => $this->resolveObjectName($scope, $scopeId),
                'action' => $this->reduceActions($actions),
                'changes' => $changes,
            ],
            $this->messageBodyProvider->prepareAuthorData($this->tokenStorage->getToken())
        ));
    }

    private function isAuditable(): bool
    {
        return
            $this->applicationState->isInstalled()
            && $this->featureChecker->isFeatureEnabled(self::FEATURE_NAME)
            && null !== $this->tokenStorage->getToken();
    }

    private function reduceActions(array $actions): string
    {
        $unique = array_unique($actions);

        return 1 === \count($unique) ? reset($unique) : Audit::ACTION_UPDATE;
    }

    /**
     * Readable name of what was configured: the name of the scope target, "Global" for the system level,
     * or a generic "<Scope> #<id>" when the target cannot be resolved.
     */
    private function resolveObjectName(string $scope, int $scopeId): string
    {
        if ('global' === $scope || $scopeId <= 0) {
            return 'Global';
        }

        $class = $this->levelProvider->getTargetEntityForScope($scope);
        $target = $class && class_exists($class)
            ? $this->doctrine->getManagerForClass($class)?->find($class, $scopeId)
            : null;
        $name = null !== $target ? (string)$this->entityNameResolver->getName($target) : '';

        return '' !== $name ? $name : sprintf('%s #%d', ucfirst(str_replace('_', ' ', $scope)), $scopeId);
    }
}
