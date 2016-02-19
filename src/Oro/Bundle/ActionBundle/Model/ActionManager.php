<?php

namespace Oro\Bundle\ActionBundle\Model;

use Doctrine\Common\Collections\Collection;

use Oro\Bundle\ActionBundle\Configuration\ActionConfigurationProvider;
use Oro\Bundle\ActionBundle\Exception\ActionNotFoundException;
use Oro\Bundle\ActionBundle\Helper\ApplicationsHelper;
use Oro\Bundle\ActionBundle\Helper\ContextHelper;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

class ActionManager
{
    const DEFAULT_FORM_TEMPLATE = 'OroActionBundle:Action:form.html.twig';
    const DEFAULT_PAGE_TEMPLATE = 'OroActionBundle:Action:page.html.twig';

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var ContextHelper */
    protected $contextHelper;

    /** @var ActionConfigurationProvider */
    protected $configurationProvider;

    /** @var ActionAssembler */
    protected $assembler;

    /** @var ApplicationsHelper */
    protected $applicationsHelper;

    /** @var array */
    private $routes = [];

    /** @var array] */
    private $entities = [];

    /** @var array */
    private $datagrids = [];

    /** @var array|Action[] */
    private $actions = [];

    /** @var bool */
    private $initialized = false;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param ContextHelper $contextHelper
     * @param ActionConfigurationProvider $configurationProvider
     * @param ActionAssembler $assembler
     * @param ApplicationsHelper $applicationsHelper
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        ContextHelper $contextHelper,
        ActionConfigurationProvider $configurationProvider,
        ActionAssembler $assembler,
        ApplicationsHelper $applicationsHelper
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->contextHelper = $contextHelper;
        $this->configurationProvider = $configurationProvider;
        $this->assembler = $assembler;
        $this->applicationsHelper = $applicationsHelper;
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
        $this->loadActions();

        $actions = $this->findActions($this->contextHelper->getContext($context));
        $actionData = $this->contextHelper->getActionData($context);
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
        $this->loadActions();

        $action = array_key_exists($actionName, $this->actions) ? $this->actions[$actionName] : null;
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

    /**
     * @param array $context
     * @return Action[]
     */
    protected function findActions(array $context)
    {
        /** @var $actions Action[] */
        $actions = [];

        if ($context[ContextHelper::ROUTE_PARAM] &&
            array_key_exists($context[ContextHelper::ROUTE_PARAM], $this->routes)
        ) {
            $actions = array_merge($actions, $this->routes[$context[ContextHelper::ROUTE_PARAM]]);
        }

        if ($context[ContextHelper::DATAGRID_PARAM] &&
            array_key_exists($context[ContextHelper::DATAGRID_PARAM], $this->datagrids)
        ) {
            $actions = $actions = array_merge($actions, $this->datagrids[$context[ContextHelper::DATAGRID_PARAM]]);
        }

        if ($context[ContextHelper::ENTITY_CLASS_PARAM] &&
            $context[ContextHelper::ENTITY_ID_PARAM] &&
            array_key_exists($context[ContextHelper::ENTITY_CLASS_PARAM], $this->entities)
        ) {
            $actions = array_merge($actions, $this->entities[$context[ContextHelper::ENTITY_CLASS_PARAM]]);
        }

        return $actions;
    }

    protected function loadActions()
    {
        if ($this->initialized) {
            return;
        }

        $configuration = $this->configurationProvider->getActionConfiguration();
        $actions = $this->assembler->assemble($configuration);

        foreach ($actions as $action) {
            if (!$action->isEnabled()) {
                continue;
            }

            if (!$this->applicationsHelper->isApplicationsValid($action)) {
                continue;
            }

            $this->mapActionRoutes($action);
            $this->mapActionEntities($action);
            $this->mapActionDatagrids($action);
            $this->actions[$action->getName()] = $action;
        }

        $this->initialized = true;
    }

    /**
     * @param Action $action
     */
    protected function mapActionRoutes(Action $action)
    {
        foreach ($action->getDefinition()->getRoutes() as $routeName) {
            $this->routes[$routeName][$action->getName()] = $action;
        }
    }

    /**
     * @param Action $action
     */
    protected function mapActionEntities(Action $action)
    {
        foreach ($action->getDefinition()->getEntities() as $entityName) {
            if (false === ($className = $this->getEntityClassName($entityName))) {
                continue;
            }
            $this->entities[$className][$action->getName()] = $action;
        }
    }

    /**
     * @param Action $action
     */
    protected function mapActionDatagrids(Action $action)
    {
        foreach ($action->getDefinition()->getDatagrids() as $datagridName) {
            $this->datagrids[$datagridName][$action->getName()] = $action;
        }
    }

    /**
     * @param string $entityName
     * @return string|bool
     */
    protected function getEntityClassName($entityName)
    {
        try {
            $entityClass = $this->doctrineHelper->getEntityClass($entityName);

            if (!class_exists($entityClass, true)) {
                return false;
            }

            $reflection = new \ReflectionClass($entityClass);

            return $reflection->getName();
        } catch (\Exception $e) {
            return false;
        }
    }
}
