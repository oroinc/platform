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

    /** @var array|Operation[] */
    private $operations = [];

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
        $this->loadActions();

        $operations = $this->findActions($this->contextHelper->getContext($context));
        $actionData = $this->contextHelper->getActionData($context);
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
        $this->loadActions();

        $operation = array_key_exists($operationName, $this->operations) ? $this->operations[$operationName] : null;
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

    /**
     * @param array $context
     * @return Operation[]
     */
    protected function findActions(array $context)
    {
        /** @var $operations Operation[] */
        $operations = [];

        if ($context[ContextHelper::ROUTE_PARAM] &&
            array_key_exists($context[ContextHelper::ROUTE_PARAM], $this->routes)
        ) {
            $operations = array_merge($operations, $this->routes[$context[ContextHelper::ROUTE_PARAM]]);
        }

        if ($context[ContextHelper::DATAGRID_PARAM] &&
            array_key_exists($context[ContextHelper::DATAGRID_PARAM], $this->datagrids)
        ) {
            $operations = $operations = array_merge(
                $operations,
                $this->datagrids[$context[ContextHelper::DATAGRID_PARAM]]
            );
        }

        if ($context[ContextHelper::ENTITY_CLASS_PARAM] &&
            $context[ContextHelper::ENTITY_ID_PARAM] &&
            array_key_exists($context[ContextHelper::ENTITY_CLASS_PARAM], $this->entities)
        ) {
            $operations = array_merge($operations, $this->entities[$context[ContextHelper::ENTITY_CLASS_PARAM]]);
        }

        return $operations;
    }

    protected function loadActions()
    {
        if ($this->initialized) {
            return;
        }

        $configuration = $this->configurationProvider->getActionConfiguration();
        $operations = $this->assembler->assemble($configuration);

        foreach ($operations as $operation) {
            if (!$operation->isEnabled()) {
                continue;
            }

            if (!$this->applicationsHelper->isApplicationsValid($operation)) {
                continue;
            }

            $this->mapActionRoutes($operation);
            $this->mapActionEntities($operation);
            $this->mapActionDatagrids($operation);
            $this->operations[$operation->getName()] = $operation;
        }

        $this->initialized = true;
    }

    /**
     * @param Operation $operation
     */
    protected function mapActionRoutes(Operation $operation)
    {
        foreach ($operation->getDefinition()->getRoutes() as $routeName) {
            $this->routes[$routeName][$operation->getName()] = $operation;
        }
    }

    /**
     * @param Operation $operation
     */
    protected function mapActionEntities(Operation $operation)
    {
        foreach ($operation->getDefinition()->getEntities() as $entityName) {
            if (false === ($className = $this->getEntityClassName($entityName))) {
                continue;
            }
            $this->entities[$className][$operation->getName()] = $operation;
        }
    }

    /**
     * @param Operation $operation
     */
    protected function mapActionDatagrids(Operation $operation)
    {
        foreach ($operation->getDefinition()->getDatagrids() as $datagridName) {
            $this->datagrids[$datagridName][$operation->getName()] = $operation;
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
