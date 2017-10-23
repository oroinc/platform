<?php

namespace Oro\Bundle\EntityBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\Get\GetContext;
use Oro\Bundle\ApiBundle\Processor\GetList\GetListContext;
use Oro\Bundle\ApiBundle\Request\ApiActions;
use Oro\Bundle\EntityBundle\Provider\EntityStructureDataProvider;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

class EntityStructureLoadDataProcessor implements ProcessorInterface
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
        $action = $context->getAction();

        if (!$this->isActionSupported($action)) {
            return;
        }

        $data = $this->entityStructureProvider->getData();

        switch ($action) {
            case ApiActions::GET:
                $id = $context->getId();
                foreach ($data as $item) {
                    if ($item->getId() === $id) {
                        $context->setResult($item);
                        break;
                    }
                }
                break;
            case ApiActions::GET_LIST:
                $context->setResult($data);
                break;
        }
    }

    /**
     * @param string $action
     *
     * @return bool
     */
    protected function isActionSupported($action)
    {
        return in_array($action, [ApiActions::GET, ApiActions::GET_LIST], true);
    }
}
