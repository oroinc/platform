<?php

namespace Oro\Bundle\ApiBundle\Processor\CollectResources;

use Oro\Bundle\ApiBundle\Request\ApiResource;
use Oro\Bundle\EntityBundle\Provider\ChainDictionaryValueListProvider;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Excludes delete action for dictionary entities.
 */
class ExcludeDeleteForDictionaries implements ProcessorInterface
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
        /** @var CollectResourcesContext $context */

        $resources = $context->getResult();
        $dictionaryEntities = $this->dictionaryProvider->getSupportedEntityClasses();

        /** @var ApiResource $resource */
        foreach ($resources as $resource) {
            if (in_array($resource->getEntityClass(), $dictionaryEntities)) {
                $excludeActions = $resource->getExcludedActions();
                if (!in_array('delete', $excludeActions)) {
                    $excludeActions[] = 'delete';
                    $resource->setExcludedActions($excludeActions);
                }
            }
        }

        $context->setResult($resources);
    }
}
