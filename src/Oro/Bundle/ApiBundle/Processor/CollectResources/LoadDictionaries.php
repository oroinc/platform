<?php

namespace Oro\Bundle\ApiBundle\Processor\CollectResources;

use Oro\Bundle\ApiBundle\Request\ApiResource;
use Oro\Bundle\EntityBundle\Provider\ChainDictionaryValueListProvider;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Collects resources for all entities marked as a dictionary.
 */
class LoadDictionaries implements ProcessorInterface
{
    private ChainDictionaryValueListProvider $dictionaryProvider;

    public function __construct(ChainDictionaryValueListProvider $dictionaryProvider)
    {
        $this->dictionaryProvider = $dictionaryProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var CollectResourcesContext $context */

        $resources = $context->getResult();
        $entities = $this->dictionaryProvider->getSupportedEntityClasses();
        foreach ($entities as $entityClass) {
            if (!$resources->has($entityClass)) {
                $resources->add(new ApiResource($entityClass));
            }
        }
    }
}
