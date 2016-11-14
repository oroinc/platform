<?php

namespace Oro\Bundle\ActionBundle\Extension;

use Oro\Bundle\ActionBundle\Helper\ApplicationsHelperInterface;
use Oro\Bundle\ActionBundle\Helper\ContextHelper;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\ButtonContext;
use Oro\Bundle\ActionBundle\Model\ButtonProviderExtensionInterface;
use Oro\Bundle\ActionBundle\Model\ButtonSearchContext;
use Oro\Bundle\ActionBundle\Model\Operation;
use Oro\Bundle\ActionBundle\Model\OperationButton;
use Oro\Bundle\ActionBundle\Model\OperationRegistry;

class OperationButtonProviderExtension implements ButtonProviderExtensionInterface
{
    /**
     * @var OperationRegistry
     */
    protected $operationRegistry;

    /**
     * @var ContextHelper
     */
    protected $contextHelper;

    /**
     * @var ApplicationsHelperInterface
     */
    protected $applicationsHelper;

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

        $operations = $this->operationRegistry->find(
            $buttonSearchContext->getEntityClass(),
            $buttonSearchContext->getRouteName(),
            $buttonSearchContext->getGridName(),
            $buttonSearchContext->getGroup()
        );

        $result = [];

        /** @var Operation $operation */
        foreach ($operations as $operation) {
            if ($operation->isAvailable($this->getActionData($buttonSearchContext))) {
                $buttonContext = $this->generateButtonContext($operation, $buttonSearchContext);
                $result[] = new OperationButton($operation, $buttonContext);
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
        $context->setDatagridName($searchContext->getGridName());
        $context->setEntity($searchContext->getEntityClass(), $searchContext->getEntityId());
        $context->setRouteName($searchContext->getRouteName());
        $context->setGroup($searchContext->getGroup());
        if ($operation->hasForm()) {
            $context->setDialogUrl($this->applicationsHelper->getDialogRoute());
        }
        $context->setExecutionUrl($this->applicationsHelper->getExecutionRoute());
        $context->setEnabled($operation->isEnabled());
        $context->setUnavailableHidden(true);

        return $context;
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
