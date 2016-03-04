<?php

namespace Oro\Bundle\ActionBundle\Model;

use Doctrine\Common\Collections\Collection;

use Oro\Bundle\ActionBundle\Exception\ActionNotFoundException;
use Oro\Bundle\ActionBundle\Helper\ContextHelper;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

class ActionManager
{
    const DEFAULT_FORM_TEMPLATE = 'OroActionBundle:Action:form.html.twig';
    const DEFAULT_PAGE_TEMPLATE = 'OroActionBundle:Action:page.html.twig';

    /** @var ActionRegistry */
    protected $actionRegistry;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var ContextHelper */
    protected $contextHelper;

    /**
     * @param ActionRegistry $actionRegistry
     * @param DoctrineHelper $doctrineHelper
     * @param ContextHelper $contextHelper
     */
    public function __construct(
        ActionRegistry $actionRegistry,
        DoctrineHelper $doctrineHelper,
        ContextHelper $contextHelper
    ) {
        $this->actionRegistry = $actionRegistry;
        $this->doctrineHelper = $doctrineHelper;
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

        $this->execute($operationName, $actionData, $errors);

        return $actionData;
    }

    /**
     * @param string $operationName
     * @param ActionData $actionData
     * @param Collection|null $errors
     * @return ActionData
     * @throws \Exception
     */
    public function execute($operationName, ActionData $actionData, Collection $errors = null)
    {
        $operation = $this->getAction($operationName, $actionData);
        $operation->execute($actionData, $errors);

        $entity = $actionData->getEntity();
        if ($entity) {
            $manager = $this->doctrineHelper->getEntityManager($entity);
            $manager->beginTransaction();

            try {
                $manager->flush();
                $manager->commit();
            } catch (\Exception $e) {
                $manager->rollback();
                throw $e;
            }
        }

        return $actionData;
    }

    /**
     * @param array|null $context
     * @return bool
     */
    public function hasActions(array $context = null)
    {
        return count($this->getActions($context)) > 0;
    }

    /**
     * @param array|null $context
     * @param bool $onlyAvailable
     * @return Operation[]
     */
    public function getActions(array $context = null, $onlyAvailable = true)
    {
        $context = $this->contextHelper->getContext($context);
        $actionData = $this->contextHelper->getActionData($context);

        $operations = $this->actionRegistry->find(
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
     * @throws ActionNotFoundException
     */
    public function getAction($operationName, ActionData $actionData, $checkAvailable = true)
    {
        $operation = $this->actionRegistry->findByName($operationName);
        if (!$operation instanceof Operation || ($checkAvailable && !$operation->isAvailable($actionData))) {
            throw new ActionNotFoundException($operationName);
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
        $operation = $this->getAction($operationName, $this->contextHelper->getActionData($context), false);

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
