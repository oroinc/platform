<?php

namespace Oro\Bundle\EntityBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Exception\ActionNotAllowedException;
use Oro\Bundle\ApiBundle\Processor\Get\GetContext;
use Oro\Bundle\ApiBundle\Processor\GetList\GetListContext;
use Oro\Bundle\ApiBundle\Request\ApiActions;
use Oro\Bundle\EntityBundle\Provider\EntityStructureDataProvider;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

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
     * {@inheritDoc}
     *
     * @var GetContext|GetListContext $context
     */
    public function process(ContextInterface $context)
    {
        if (!$this->isActionSupported($context->getAction())) {
            throw new ActionNotAllowedException();
        }

        $context->setResult($this->entityStructureProvider->getData());
    }

    /**
     * @param string $action
     *
     * @return bool
     */
    protected function isActionSupported($action)
    {
        return ApiActions::GET_LIST === $action;
    }
}
