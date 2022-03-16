<?php

namespace Oro\Bundle\WorkflowBundle\EventListener;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowDefinitionRepository;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Event\WorkflowEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * This class is used to check whether there is a workflow associated with this entity.
 */
class WorkflowAwareCache implements EventSubscriberInterface
{
    private const ACTIVE_WORKFLOW_RELATED_CLASSES_KEY = 'active_workflow_related';
    private const WORKFLOW_RELATED_CLASSES_KEY = 'all_workflow_related';

    private CacheInterface $cache;
    private DoctrineHelper $doctrineHelper;
    private ?WorkflowDefinitionRepository $definitionRepository = null;

    public function __construct(CacheInterface $cache, DoctrineHelper $doctrineHelper)
    {
        $this->cache = $cache;
        $this->doctrineHelper = $doctrineHelper;
    }

    public function hasRelatedActiveWorkflows(object|string $entity): bool
    {
        return $this->hasRelatedWorkflowsForEntity($entity, true);
    }

    public function hasRelatedWorkflows(object|string $entity): bool
    {
        return $this->hasRelatedWorkflowsForEntity($entity, false);
    }

    private function hasRelatedWorkflowsForEntity(object|string $entity, bool $activeWorkflowsOnly): bool
    {
        $key = $activeWorkflowsOnly ? self::ACTIVE_WORKFLOW_RELATED_CLASSES_KEY : self::WORKFLOW_RELATED_CLASSES_KEY;
        $class = $this->doctrineHelper->getEntityClass($entity);

        $classes = $this->cache->get($key, function () use ($activeWorkflowsOnly) {
            return $this->fetchClasses($activeWorkflowsOnly);
        });

        return array_key_exists($class, $classes);
    }

    private function fetchClasses(bool $activeWorkflowsOnly = false): array
    {
        if (null === $this->definitionRepository) {
            $this->definitionRepository = $this->doctrineHelper->getEntityRepository(WorkflowDefinition::class);
        }

        return array_flip($this->definitionRepository->getAllRelatedEntityClasses($activeWorkflowsOnly));
    }

    public function build(): void
    {
        $this->invalidateRelated();
        $this->invalidateActiveRelated();
        $this->cache->get(self::ACTIVE_WORKFLOW_RELATED_CLASSES_KEY, function () {
            return $this->fetchClasses(true);
        });
        $this->cache->get(self::WORKFLOW_RELATED_CLASSES_KEY, function () {
            return $this->fetchClasses();
        });
    }

    public function invalidateRelated(): void
    {
        $this->cache->delete(self::WORKFLOW_RELATED_CLASSES_KEY);
    }

    public function invalidateActiveRelated(): void
    {
        $this->cache->delete(self::ACTIVE_WORKFLOW_RELATED_CLASSES_KEY);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            WorkflowEvents::WORKFLOW_AFTER_UPDATE => [['invalidateRelated'], ['invalidateActiveRelated']],
            WorkflowEvents::WORKFLOW_AFTER_CREATE => [['invalidateRelated'], ['invalidateActiveRelated']],
            WorkflowEvents::WORKFLOW_AFTER_DELETE => [['invalidateRelated'], ['invalidateActiveRelated']],
            WorkflowEvents::WORKFLOW_ACTIVATED => ['invalidateActiveRelated'],
            WorkflowEvents::WORKFLOW_DEACTIVATED => ['invalidateActiveRelated']
        ];
    }
}
