<?php

namespace Oro\Bundle\EntityBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\GetList\GetListContext;
use Oro\Bundle\ApiBundle\Request\ApiActions;
use Oro\Bundle\EntityBundle\Provider\EntityStructureDataProvider;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Loads detailed information about entities.
 */
class LoadEntityStructureData implements ProcessorInterface
{
    /** @var EntityStructureDataProvider */
    protected $entityStructureProvider;

    /**
     * @param EntityStructureDataProvider $entityStructureProvider
     */
    public function __construct(EntityStructureDataProvider $entityStructureProvider)
    {
        $this->entityStructureProvider = $entityStructureProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var GetListContext $context */

        // disabling the garbage collector gives a significant performance gain (about 50% faster)
        $gcEnabled = gc_enabled();
        if ($gcEnabled) {
            gc_disable();
        }
        try {
            $context->setResult($this->entityStructureProvider->getData());
        } finally {
            if ($gcEnabled) {
                gc_enable();
            }
        }
    }

    /**
     * @param string $action
     *
     * @return bool
     * @deprecated will be removed in 3.1
     */
    protected function isActionSupported($action)
    {
        return ApiActions::GET_LIST === $action;
    }
}
