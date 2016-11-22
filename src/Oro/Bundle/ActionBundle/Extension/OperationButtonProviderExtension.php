<?php

namespace Oro\Bundle\ActionBundle\Extension;

use Oro\Bundle\ActionBundle\Helper\ApplicationsHelperInterface;
use Oro\Bundle\ActionBundle\Helper\ContextHelper;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\ButtonContext;
use Oro\Bundle\ActionBundle\Model\ButtonSearchContext;
use Oro\Bundle\ActionBundle\Model\Operation;
use Oro\Bundle\ActionBundle\Model\OperationButton;
use Oro\Bundle\ActionBundle\Model\OperationRegistry;

class OperationButtonProviderExtension implements ButtonProviderExtensionInterface
{
    /** @var OperationRegistry */
    protected $operationRegistry;

    /** @var ContextHelper */
    protected $contextHelper;

    /** @var ApplicationsHelperInterface */
    protected $applicationsHelper;

    /**
     * @param OperationRegistry $operationRegistry
     * @param ContextHelper $contextHelper
     * @param ApplicationsHelperInterface $applicationsHelper
     */
    public function __construct(
        OperationRegistry $operationRegistry,
        ContextHelper $contextHelper,
        ApplicationsHelperInterface $applicationsHelper
    ) {
        $this->operationRegistry = $operationRegistry;
        $this->contextHelper = $contextHelper;
        $this->applicationsHelper = $applicationsHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function find(ButtonSearchContext $buttonSearchContext)
    {
        $operations = $this->getOperations($buttonSearchContext);
        $result = [];

        foreach ($operations as $operation) {
            $actionData = $this->getActionData($buttonSearchContext);

            if ($operation->isAvailable($actionData)) {
                $result[] = new OperationButton(
                    $operation,
                    $this->generateButtonContext($operation, $buttonSearchContext)
                );
            }
        }

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
        $context = new ButtonContext();
        $context->setUnavailableHidden(true)
            ->setDatagridName($searchContext->getGridName())
            ->setEntity($searchContext->getEntityClass(), $searchContext->getEntityId())
            ->setRouteName($searchContext->getRouteName())
            ->setGroup($searchContext->getGroup())
            ->setExecutionRoute($this->applicationsHelper->getExecutionRoute())
            ->setEnabled($operation->isEnabled());

        if ($operation->hasForm()) {
            $context->setFormDialogRoute($this->applicationsHelper->getFormDialogRoute());
            $context->setFormPageRoute($this->applicationsHelper->getFormPageRoute());
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
