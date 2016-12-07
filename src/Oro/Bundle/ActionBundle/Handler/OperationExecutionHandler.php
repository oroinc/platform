<?php

namespace Oro\Bundle\ActionBundle\Handler;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\ActionBundle\Exception\OperationNotFoundException;
use Oro\Bundle\ActionBundle\Helper\ContextHelper;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\Operation;
use Oro\Bundle\ActionBundle\Model\OperationRegistry;
use Symfony\Component\HttpFoundation\Request;

class OperationExecutionHandler
{
    /**
     * @var ContextHelper
     */
    private $contextHelper;

    /**
     * @var OperationRegistry
     */
    private $registry;

    public function __construct(ContextHelper $contextHelper, OperationRegistry $registry)
    {
        $this->contextHelper = $contextHelper;
        $this->registry = $registry;
    }

    /**
     * @param Request $request
     * @return ActionData
     */
    public function getData(Request $request)
    {
        return $this->contextHelper->getActionData();
    }

    /**
     * @param string $operationName
     * @param ActionData $actionData
     * @param Request $request
     * @param Collection $errors
     * @return ActionData
     * @throws OperationNotFoundException
     */
    public function execute($operationName, ActionData $actionData, Request $request, Collection $errors)
    {
        $operation = $this->registry->findByName($operationName);

        if (!$operation instanceof Operation || !$operation->isAvailable($actionData)) {
            throw new OperationNotFoundException($operationName);
        }

        return $operation->execute($actionData, $errors);
    }
}
