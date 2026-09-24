<?php

namespace Oro\Bundle\DataAuditBundle\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Oro\Bundle\DataAuditBundle\Entity\Audit;
use Oro\Bundle\DataAuditBundle\Model\AuditEntry;
use Oro\Bundle\DataAuditBundle\Model\ConfigAuditValueNormalizer;
use Oro\Bundle\DataAuditBundle\Provider\AuditMessageBodyProvider;
use Oro\Bundle\DataAuditBundle\Provider\ConfigAuditLevelProvider;
use Oro\Bundle\DataAuditBundle\Service\AuditEntryRecorder;
use Oro\Bundle\DistributionBundle\Handler\ApplicationState;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Records every system configuration change (oro_config.update_after) as a first-class Data Audit entry
 * with who / when / old / new.
 */
class ConfigChangeAuditListener
{
    private ?AuditEntryRecorder $auditEntryRecorder = null;

    public function __construct(
        private readonly ManagerRegistry $doctrine,
        TokenStorageInterface $tokenStorage,
        private readonly EntityNameResolver $entityNameResolver,
        FeatureChecker $featureChecker,
        MessageProducerInterface $messageProducer,
        ApplicationState $applicationState,
        private readonly ConfigAuditValueNormalizer $valueNormalizer,
        AuditMessageBodyProvider $messageBodyProvider,
        private readonly ConfigAuditLevelProvider $levelProvider
    ) {
    }

    public function setAuditEntryRecorder(AuditEntryRecorder $auditEntryRecorder): self
    {
        $this->auditEntryRecorder = $auditEntryRecorder;

        return $this;
    }

    public function onConfigUpdate(ConfigUpdateEvent $event): void
    {
        $changeSet = $event->getChangeSet() + $event->getUseParentScopeChanges();
        if (!$changeSet || !$this->auditEntryRecorder?->isEnabled()) {
            return;
        }

        $actions = [];
        $changes = [];
        foreach ($changeSet as $name => $change) {
            $action = $change['action'] ?? Audit::ACTION_UPDATE;
            $actions[] = $action;
            $changes[$name] = $this->valueNormalizer->normalize(
                $name,
                Audit::ACTION_CREATE === $action ? null : ($change['old'] ?? null),
                Audit::ACTION_REMOVE === $action ? null : ($change['new'] ?? null)
            );
        }

        $scope = $event->getScope();
        $scopeId = $event->getScopeId();

        $entry = new AuditEntry(
            $this->levelProvider->getClassForScope($scope),
            (string)$scopeId,
            $this->resolveObjectName($scope, $scopeId),
            $this->reduceActions($actions)
        );
        foreach ($changes as $name => $change) {
            $entry->addChange($name, $change['old'], $change['new'], $change['type']);
        }

        $this->auditEntryRecorder->record($entry);
    }

    private function reduceActions(array $actions): string
    {
        $unique = array_unique($actions);

        return 1 === \count($unique) ? reset($unique) : Audit::ACTION_UPDATE;
    }

    /**
     * Readable name of what was configured: the name of the scope target, "Global" for the system level,
     * or a generic "<Scope> #<ID>" when the target cannot be resolved.
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
