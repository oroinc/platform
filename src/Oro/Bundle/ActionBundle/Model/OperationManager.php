<?php

namespace Oro\Bundle\ActionBundle\Model;

use Doctrine\Common\Collections\Collection;

use Oro\Bundle\ActionBundle\Exception\OperationNotFoundException;
use Oro\Bundle\ActionBundle\Helper\ContextHelper;

class OperationManager
{
    const DEFAULT_FORM_TEMPLATE = 'OroActionBundle:Operation:form.html.twig';
    const DEFAULT_PAGE_TEMPLATE = 'OroActionBundle:Operation:page.html.twig';

    /** @var OperationRegistry */
    protected $operationRegistry;

    /** @var ActionGroupRegistry */
    protected $actionGroupRegistry;

    /** @var ContextHelper */
    protected $contextHelper;

    /**
     * @param OperationRegistry $operationRegistry
     * @param ActionGroupRegistry $actionGroupRegistry
     * @param ContextHelper $contextHelper
     */
    public function __construct(
        OperationRegistry $operationRegistry,
        ActionGroupRegistry $actionGroupRegistry,
        ContextHelper $contextHelper
    ) {
        $this->operationRegistry = $operationRegistry;
        $this->actionGroupRegistry = $actionGroupRegistry;
        $this->contextHelper = $contextHelper;
    }

    /**
     * @param string $operationName
     * @param array|null $context
     * @param Collection|null $errors
     * @return ActionData
     */
    public function executeByContext($operationName, array $context = null, Collection $errors = null)
    {
        $actionData = $this->contextHelper->getActionData($context);

        return $this->execute($operationName, $actionData, $errors);
    }

    /**
     * @param string $operationName
     * @param ActionData $actionData
     * @param Collection|null $errors
     * @return ActionData
     */
    public function execute($operationName, ActionData $actionData, Collection $errors = null)
    {
        $this->getOperation($operationName, $actionData)->execute($actionData, $errors);

        return $actionData;
    }

    /**
     * @param array|null $context
     * @return bool
     */
    public function hasOperations(array $context = null)
    {
        return count($this->getOperations($context)) > 0;
    }

    /**
     * @param array|null $context
     * @param bool $onlyAvailable
     * @return Operation[]
     */
    public function getOperations(array $context = null, $onlyAvailable = true)
    {
        $context = $this->contextHelper->getContext($context);
        $actionData = $this->contextHelper->getActionData($context);

        $operations = $this->operationRegistry->find(
            $context[ContextHelper::ENTITY_ID_PARAM] ? $context[ContextHelper::ENTITY_CLASS_PARAM] : null,
            $context[ContextHelper::ROUTE_PARAM],
            $context[ContextHelper::DATAGRID_PARAM],
            $context[ContextHelper::GROUP_PARAM]
        );

        if ($onlyAvailable) {
            $operations = array_filter($operations, function (Operation $operation) use ($actionData) {
                return $operation->isAvailable($actionData);
            });
        }

        uasort($operations, function (Operation $operation1, Operation $operation2) {
            return $operation1->getDefinition()->getOrder() - $operation2->getDefinition()->getOrder();
        });

        return $operations;
    }

    /**
     * @param string $operationName
     * @param ActionData $actionData
     * @param bool $checkAvailable
     * @return Operation
     * @throws OperationNotFoundException
     */
    public function getOperation($operationName, ActionData $actionData, $checkAvailable = true)
    {
        $operation = $this->operationRegistry->findByName($operationName);
        if (!$operation instanceof Operation || ($checkAvailable && !$operation->isAvailable($actionData))) {
            throw new OperationNotFoundException($operationName);
        }

        return $operation;
    }

    /**
     * @param string $operationName
     * @param array|null $context
     * @return string
     */
    public function getFrontendTemplate($operationName, array $context = null)
    {
        $template = self::DEFAULT_FORM_TEMPLATE;
        $operation = $this->getOperation($operationName, $this->contextHelper->getActionData($context), false);

        if ($operation) {
            $frontendOptions = $operation->getDefinition()->getFrontendOptions();

            if (array_key_exists('template', $frontendOptions)) {
                $template = $frontendOptions['template'];
            } elseif (array_key_exists('show_dialog', $frontendOptions) && !$frontendOptions['show_dialog']) {
                $template = self::DEFAULT_PAGE_TEMPLATE;
            }
        }

        return $template;
    }
}
