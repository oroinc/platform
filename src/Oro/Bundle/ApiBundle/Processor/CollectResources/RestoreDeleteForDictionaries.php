<?php

namespace Oro\Bundle\ApiBundle\Processor\CollectResources;

use Oro\Bundle\ApiBundle\Request\ApiResource;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Restores delete action for dictionary BusinessUnit, Tag and User entities.
 */
class RestoreDeleteForDictionaries implements ProcessorInterface
{
    protected $dictionaries = [
        'Oro\Bundle\OrganizationBundle\Entity\BusinessUnit',
        'Oro\Bundle\TagBundle\Entity\Tag',
        'Oro\Bundle\UserBundle\Entity\User'
    ];

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var CollectResourcesContext $context */

        $resources = $context->getResult();

        /** @var ApiResource $resource */
        foreach ($resources as $resource) {
            if (in_array($resource->getEntityClass(), $this->dictionaries)) {
                $excludeActions = $resource->getExcludedActions();
                foreach ($excludeActions as $id => $action) {
                    if ($action === 'delete') {
                        unset($excludeActions[$id]);
                    }
                }
                $resource->setExcludedActions($excludeActions);
            }
        }

        $context->setResult($resources);
    }
}
