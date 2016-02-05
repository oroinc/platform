<?php

namespace Oro\Bundle\ApiBundle\Processor\CollectPublicResources;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Request\PublicResource;
use Oro\Bundle\EntityBundle\Provider\ChainDictionaryValueListProvider;

/**
 * Collects resources for all entities marked as a dictionary.
 */
class LoadDictionaries implements ProcessorInterface
{
    /** @var ChainDictionaryValueListProvider */
    protected $dictionaryProvider;

    /**
     * @param ChainDictionaryValueListProvider $dictionaryProvider
     */
    public function __construct(ChainDictionaryValueListProvider $dictionaryProvider)
    {
        $this->dictionaryProvider = $dictionaryProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var CollectPublicResourcesContext $context */

        $resources = $context->getResult();
        $entities  = $this->dictionaryProvider->getSupportedEntityClasses();
        foreach ($entities as $entityClass) {
            $resources->add(new PublicResource($entityClass));
        }
    }
}
