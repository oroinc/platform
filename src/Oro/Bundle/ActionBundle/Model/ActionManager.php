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
     * @param string $actionName
     * @param array|null $context
     * @param Collection|null $errors
     * @return ActionData
     */
    public function executeByContext($actionName, array $context = null, Collection $errors = null)
    {
        $actionData = $this->contextHelper->getActionData($context);

        $this->execute($actionName, $actionData, $errors);

        return $actionData;
    }

    /**
     * @param string $actionName
     * @param ActionData $actionData
     * @param Collection|null $errors
     * @return ActionData
     * @throws \Exception
     */
    public function execute($actionName, ActionData $actionData, Collection $errors = null)
    {
        $action = $this->getAction($actionName, $actionData);
        $action->execute($actionData, $errors);

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
     * @return Action[]
     */
    public function getActions(array $context = null, $onlyAvailable = true)
    {
        $context = $this->contextHelper->getContext($context);
        $actionData = $this->contextHelper->getActionData($context);

        $actions = $this->actionRegistry->find(
            $context[ContextHelper::ENTITY_ID_PARAM] ? $context[ContextHelper::ENTITY_CLASS_PARAM] : null,
            $context[ContextHelper::ROUTE_PARAM],
            $context[ContextHelper::DATAGRID_PARAM],
            $context[ContextHelper::GROUP_PARAM]
        );

        if ($onlyAvailable) {
            $actions = array_filter($actions, function (Action $action) use ($actionData) {
                return $action->isAvailable($actionData);
            });
        }

        uasort($actions, function (Action $action1, Action $action2) {
            return $action1->getDefinition()->getOrder() - $action2->getDefinition()->getOrder();
        });

        return $actions;
    }

    /**
     * @param string $actionName
     * @param ActionData $actionData
     * @return Action
     * @throws ActionNotFoundException
     */
    public function getAction($actionName, ActionData $actionData)
    {
        $action = $this->actionRegistry->findByName($actionName);
        if (!$action instanceof Action || !$action->isAvailable($actionData)) {
            throw new ActionNotFoundException($actionName);
        }

        return $action;
    }

    /**
     * @param string $actionName
     * @param array|null $context
     * @return string
     */
    public function getFrontendTemplate($actionName, array $context = null)
    {
        $template = self::DEFAULT_FORM_TEMPLATE;
        $action = $this->getAction($actionName, $this->contextHelper->getActionData($context));

        if ($action) {
            $frontendOptions = $action->getDefinition()->getFrontendOptions();

            if (array_key_exists('template', $frontendOptions)) {
                $template = $frontendOptions['template'];
            } elseif (array_key_exists('show_dialog', $frontendOptions) && !$frontendOptions['show_dialog']) {
                $template = self::DEFAULT_PAGE_TEMPLATE;
            }
        }

        return $template;
    }
}
