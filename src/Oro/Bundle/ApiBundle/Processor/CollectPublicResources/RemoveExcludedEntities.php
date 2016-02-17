<?php

namespace Oro\Bundle\ApiBundle\Processor\CollectPublicResources;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Request\PublicResource;
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
        /** @var CollectPublicResourcesContext $context */

        $context->setResult(
            $context->getResult()->filter(
                function (PublicResource $resource) {
                    return !$this->entityExclusionProvider->isIgnoredEntity($resource->getEntityClass());
                }
            )
        );
    }
}
