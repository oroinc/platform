<?php

namespace Oro\Bundle\WorkflowBundle\EventListener;

use Doctrine\Common\Cache\Cache;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowDefinitionRepository;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Event\WorkflowEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * This class is used to check whether there is a workflow associated with this entity.
 */
class WorkflowAwareCache implements EventSubscriberInterface
{
    const ACTIVE_WORKFLOW_RELATED_CLASSES_KEY = 'active_workflow_related';
    const WORKFLOW_RELATED_CLASSES_KEY = 'all_workflow_related';

    /** @var Cache */
    private $cache;

    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var WorkflowDefinitionRepository */
    private $definitionRepository;

    /**
     * @param Cache $cache
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(Cache $cache, DoctrineHelper $doctrineHelper)
    {
        $this->cache = $cache;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param object $entity
     * @return bool
     */
    public function hasRelatedActiveWorkflows($entity)
    {
        return $this->hasRelatedWorkflowsForEntity($entity, true);
    }

    /**
     * @param object $entity
     * @return bool
     */
    public function hasRelatedWorkflows($entity)
    {
        return $this->hasRelatedWorkflowsForEntity($entity, false);
    }

    /**
     * @param object $entity
     * @param bool $activeWorkflowsOnly
     * @return bool
     */
    private function hasRelatedWorkflowsForEntity($entity, bool $activeWorkflowsOnly)
    {
        $key = $activeWorkflowsOnly ? self::ACTIVE_WORKFLOW_RELATED_CLASSES_KEY : self::WORKFLOW_RELATED_CLASSES_KEY;
        $class = $this->doctrineHelper->getEntityClass($entity);

        $classes = $this->cache->fetch($key);
        if (false === $classes) {
            $classes = $this->fetchClasses($activeWorkflowsOnly);
            $this->cache->save($key, $classes);
        }

        return array_key_exists($class, $classes);
    }

    /**
     * @param bool $activeWorkflowsOnly
     * @return array
     */
    private function fetchClasses(bool $activeWorkflowsOnly = false)
    {
        if (null === $this->definitionRepository) {
            $this->definitionRepository = $this->doctrineHelper->getEntityRepository(WorkflowDefinition::class);
        }

        return array_flip($this->definitionRepository->getAllRelatedEntityClasses($activeWorkflowsOnly));
    }

    public function invalidateRelated()
    {
        $this->cache->delete(self::WORKFLOW_RELATED_CLASSES_KEY);
    }

    public function invalidateActiveRelated()
    {
        $this->cache->delete(self::ACTIVE_WORKFLOW_RELATED_CLASSES_KEY);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
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
