<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Environment;

class SkippedEntityProvider implements SkippedEntityProviderInterface
{
    /** @var SkippedEntityProviderInterface */
    private $innerProvider;

    /** @var array [entity class => [action, ...]] */
    private $skippedEntities = [];

    public function __construct(SkippedEntityProviderInterface $innerProvider)
    {
        $this->innerProvider = $innerProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function isSkippedEntity(string $entityClass, string $action): bool
    {
        if (isset($this->skippedEntities[$entityClass])) {
            return
                !$this->skippedEntities[$entityClass]
                || \in_array($action, $this->skippedEntities[$entityClass], true);
        }

        return $this->innerProvider->isSkippedEntity($entityClass, $action);
    }

    /**
     * @param string   $entityClass
     * @param string[] $actions
     */
    public function addSkippedEntity(string $entityClass, array $actions = []): void
    {
        $this->skippedEntities[$entityClass] = $actions;
    }
}
