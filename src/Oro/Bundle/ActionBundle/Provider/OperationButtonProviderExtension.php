<?php

namespace Oro\Bundle\ActionBundle\Provider;

use Oro\Bundle\ActionBundle\Helper\ApplicationsHelperInterface;
use Oro\Bundle\ActionBundle\Helper\ContextHelper;
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

        $actionData = $this->contextHelper->getActionData([
            ContextHelper::ENTITY_ID_PARAM => $buttonSearchContext->getEntityId(),
            ContextHelper::ENTITY_CLASS_PARAM => $buttonSearchContext->getEntityClass(),
            ContextHelper::DATAGRID_PARAM => $buttonSearchContext->getGridName(),
            ContextHelper::FROM_URL_PARAM => $buttonSearchContext->getReferrer(),
            ContextHelper::ROUTE_PARAM => $buttonSearchContext->getRouteName(),
        ]);

        $result = [];

        /** @var Operation $operation */
        foreach ($operations as $operation) {
            if ($operation->isAvailable($actionData)) {
                $buttonContext = $this->generateButtonContext($buttonSearchContext);
                if ($operation->hasForm()) {
                    $buttonContext->setDialogUrl($this->applicationsHelper->getDialogRoute());
                }
                $buttonContext->setExecutionUrl($this->applicationsHelper->getExecutionRoute());
                $buttonContext->setEnabled($operation->isEnabled());
                $buttonContext->setUnavailableHidden(true);
                $result[] = new OperationButton($operation, $buttonContext);
            }
        }

        return $result;
    }

    /**
     * @param ButtonSearchContext $searchContext
     *
     * @return ButtonContext
     */
    protected function generateButtonContext(ButtonSearchContext $searchContext)
    {
        $context = new ButtonContext();
        $context->setDatagridName($searchContext->getGridName());
        $context->setEntity($searchContext->getEntityClass(), $searchContext->getEntityId());
        $context->setRouteName($searchContext->getRouteName());
        $context->setGroup($searchContext->getGroup());

        return $context;
    }
}
