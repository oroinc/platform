<?php

namespace Oro\Bundle\ApiBundle\Processor\CollectResources;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\EntityBundle\Provider\ExclusionProviderInterface;

/**
 * Removes resources for excluded entities.
 */
class RemoveExcludedEntities implements ProcessorInterface
{
    /** @var ExclusionProviderInterface */
    protected $entityExclusionProvider;

    /**
     * @param ExclusionProviderInterface $entityExclusionProvider
     */
    public function __construct(ExclusionProviderInterface $entityExclusionProvider)
    {
        $this->entityExclusionProvider = $entityExclusionProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var CollectResourcesContext $context */

        $resources = $context->getResult();
        $entityClasses = array_keys($resources->toArray());
        foreach ($entityClasses as $entityClass) {
            if ($this->entityExclusionProvider->isIgnoredEntity($entityClass)) {
                $resources->remove($entityClass);
            }
        }
    }
}
