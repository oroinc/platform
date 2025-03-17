<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\EventListener\Extension;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\TestFrameworkBundle\Entity\WorkflowAwareEntity;
use Oro\Bundle\WorkflowBundle\Cache\EventTriggerCache;
use Oro\Bundle\WorkflowBundle\Entity\EventTriggerInterface;
use Oro\Bundle\WorkflowBundle\EventListener\Extension\AbstractEventTriggerExtension;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

abstract class AbstractEventTriggerExtensionTestCase extends TestCase
{
    use EntityTrait;

    protected const ENTITY_CLASS = WorkflowAwareEntity::class;
    protected const ENTITY_ID = 42;
    protected const FIELD = 'name';

    /** @var MockObject */
    protected $repository;
    protected EntityManagerInterface&MockObject $entityManager;
    protected DoctrineHelper&MockObject $doctrineHelper;
    protected EventTriggerCache&MockObject $triggerCache;
    protected AbstractEventTriggerExtension $extension;
    protected array $triggers = [];

    #[\Override]
    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->triggerCache = $this->createMock(EventTriggerCache::class);
    }

    protected function callPreFunctionByEventName(
        string $event,
        object $entity,
        array $changeSet = [],
        ?object $extension = null
    ): void {
        if (null === $extension) {
            $extension = $this->extension;
        }
        switch ($event) {
            case EventTriggerInterface::EVENT_CREATE:
            case EventTriggerInterface::EVENT_DELETE:
                $extension->schedule($entity, $event);
                break;
            case EventTriggerInterface::EVENT_UPDATE:
                $extension->schedule($entity, $event, $changeSet);
                break;
        }
    }

    protected function prepareRepository(?array $triggers = null): void
    {
        $this->repository->expects(self::once())
            ->method('findAllWithDefinitions')
            ->with(true)
            ->willReturn($triggers ?: $this->getTriggers());
    }

    protected function prepareTriggerCache(string $entityClass, string $event, bool $hasTrigger = true): void
    {
        $this->triggerCache->expects(self::any())
            ->method('hasTrigger')
            ->with($entityClass, $event)
            ->willReturn($hasTrigger);
    }

    /**
     * @param string|null $triggerName
     *
     * @return EventTriggerInterface[]|EventTriggerInterface
     */
    abstract protected function getTriggers(?string $triggerName = null): array|object;

    /**
     * @param EventTriggerInterface[] $triggers
     *
     * @return array
     */
    protected function getExpectedTriggers(array $triggers): array
    {
        $expectedTriggers = [];

        foreach ($triggers as $trigger) {
            $entityClass = $trigger->getEntityClass();
            $event = $trigger->getEvent();
            $field = $trigger->getField();

            if ($event === EventTriggerInterface::EVENT_UPDATE) {
                if ($field) {
                    $expectedTriggers[$entityClass][$event]['field'][$field][] = $trigger;
                } else {
                    $expectedTriggers[$entityClass][$event]['entity'][] = $trigger;
                }
            } else {
                $expectedTriggers[$entityClass][$event][] = $trigger;
            }
        }

        return $expectedTriggers;
    }

    protected function getMainEntity(?int $id = self::ENTITY_ID, array $fields = []): WorkflowAwareEntity
    {
        return $this->getEntity(
            static::ENTITY_CLASS,
            array_merge(['id' => $id], $fields)
        );
    }
}
