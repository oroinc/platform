<?php

namespace Oro\Bundle\ActionBundle\Extension;

use Oro\Bundle\ActionBundle\Helper\ContextHelper;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\ButtonContext;
use Oro\Bundle\ActionBundle\Model\ButtonSearchContext;
use Oro\Bundle\ActionBundle\Model\Operation;
use Oro\Bundle\ActionBundle\Model\OperationButton;
use Oro\Bundle\ActionBundle\Model\OperationRegistry;
use Oro\Bundle\ActionBundle\Provider\RouteProviderInterface;

class OperationButtonProviderExtension implements ButtonProviderExtensionInterface
{
    /** @var OperationRegistry */
    protected $operationRegistry;

    /** @var ContextHelper */
    protected $contextHelper;

    /** @var RouteProviderInterface */
    protected $routeProvider;

    /** @var ButtonContext */
    private $baseButtonContext;

    /**
     * @param OperationRegistry $operationRegistry
     * @param ContextHelper $contextHelper
     * @param RouteProviderInterface $routeProvider
     */
    public function __construct(
        OperationRegistry $operationRegistry,
        ContextHelper $contextHelper,
        RouteProviderInterface $routeProvider
    ) {
        $this->operationRegistry = $operationRegistry;
        $this->contextHelper = $contextHelper;
        $this->routeProvider = $routeProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function find(ButtonSearchContext $buttonSearchContext)
    {
        $operations = $this->getOperations($buttonSearchContext);
        $actionData = $this->getActionData($buttonSearchContext);
        $result = [];

        foreach ($operations as $operation) {
            $currentActionData = clone $actionData;

            if ($operation->isAvailable($currentActionData)) {
                $result[] = new OperationButton(
                    $operation,
                    $this->generateButtonContext($operation, $buttonSearchContext),
                    $currentActionData
                );
            }
        }

        $this->baseButtonContext = null;

        return $result;
    }

    /**
     * @param Operation $operation
     * @param ButtonSearchContext $searchContext
     *
     * @return ButtonContext
     */
    protected function generateButtonContext(Operation $operation, ButtonSearchContext $searchContext)
    {
        if (!$this->baseButtonContext) {
            $this->baseButtonContext = new ButtonContext();
            $this->baseButtonContext->setUnavailableHidden(true)
                ->setDatagridName($searchContext->getGridName())
                ->setEntity($searchContext->getEntityClass(), $searchContext->getEntityId())
                ->setRouteName($searchContext->getRouteName())
                ->setGroup($searchContext->getGroup())
                ->setExecutionRoute($this->routeProvider->getExecutionRoute());
        }

        $context = clone $this->baseButtonContext;
        $context->setEnabled($operation->isEnabled());

        if ($operation->hasForm()) {
            $context->setFormDialogRoute($this->routeProvider->getFormDialogRoute());
            $context->setFormPageRoute($this->routeProvider->getFormPageRoute());
        }

        return $context;
    }

    /**
     * @param ButtonSearchContext $buttonSearchContext
     *
     * @return array|Operation[]
     */
    protected function getOperations(ButtonSearchContext $buttonSearchContext)
    {
        return $this->operationRegistry->find(
            $buttonSearchContext->getEntityClass(),
            $buttonSearchContext->getRouteName(),
            $buttonSearchContext->getGridName(),
            $buttonSearchContext->getGroup()
        );
    }

    /**
     * @param ButtonSearchContext $searchContext
     *
     * @return ActionData
     */
    protected function getActionData(ButtonSearchContext $searchContext)
    {
        return $this->contextHelper->getActionData([
            ContextHelper::ENTITY_ID_PARAM => $searchContext->getEntityId(),
            ContextHelper::ENTITY_CLASS_PARAM => $searchContext->getEntityClass(),
            ContextHelper::DATAGRID_PARAM => $searchContext->getGridName(),
            ContextHelper::FROM_URL_PARAM => $searchContext->getReferrer(),
            ContextHelper::ROUTE_PARAM => $searchContext->getRouteName(),
        ]);
    }
}
