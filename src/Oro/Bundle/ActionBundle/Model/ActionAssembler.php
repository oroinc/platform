<?php

namespace Oro\Bundle\ActionBundle\Model;

use Doctrine\ORM\ORMException;

use Oro\Bundle\ActionBundle\Form\Type\ActionType;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use Oro\Component\Action\Action\ActionFactory as FunctionFactory;
use Oro\Component\ConfigExpression\ExpressionFactory as ConditionFactory;

class ActionAssembler extends AbstractAssembler
{
    /** @var FunctionFactory */
    private $functionFactory;

    /** @var ConditionFactory */
    private $conditionFactory;

    /** @var AttributeAssembler */
    private $attributeAssembler;

    /** @var FormOptionsAssembler */
    private $formOptionsAssembler;

    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var array */
    private $entityNames = [];

    /**
     * @param FunctionFactory $functionFactory
     * @param ConditionFactory $conditionFactory
     * @param AttributeAssembler $attributeAssembler
     * @param FormOptionsAssembler $formOptionsAssembler
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(
        FunctionFactory $functionFactory,
        ConditionFactory $conditionFactory,
        AttributeAssembler $attributeAssembler,
        FormOptionsAssembler $formOptionsAssembler,
        DoctrineHelper $doctrineHelper
    ) {
        $this->functionFactory = $functionFactory;
        $this->conditionFactory = $conditionFactory;
        $this->attributeAssembler = $attributeAssembler;
        $this->formOptionsAssembler = $formOptionsAssembler;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param array $configuration
     * @return Action[]
     */
    public function assemble(array $configuration)
    {
        $actions = [];

        foreach ($configuration as $actionName => $options) {
            $actions[$actionName] = new Action(
                $this->functionFactory,
                $this->conditionFactory,
                $this->attributeAssembler,
                $this->formOptionsAssembler,
                $this->assembleDefinition($actionName, $options)
            );
        }

        return $actions;
    }

    /**
     * @param string $actionName
     * @param array $options
     * @return ActionDefinition
     */
    protected function assembleDefinition($actionName, array $options)
    {
        $this->assertOptions($options, ['label'], $actionName);
        $actionDefinition = new ActionDefinition();

        $actionDefinition
            ->setName($actionName)
            ->setLabel($this->getOption($options, 'label'))
            ->setSubstituteAction($this->getOption($options, 'substitute_action', null))
            ->setForAllEntities($this->getOption($options, 'for_all_entities', false))
            ->setEntities($this->filterEntities($this->getOption($options, 'entities', [])))
            ->setExcludeEntities($this->filterEntities($this->getOption($options, 'exclude_entities', [])))
            ->setForAllDatagrids($this->getOption($options, 'for_all_datagrids', false))
            ->setDatagrids($this->getOption($options, 'datagrids', []))
            ->setRoutes($this->getOption($options, 'routes', []))
            ->setGroups($this->getOption($options, 'groups', []))
            ->setApplications($this->getOption($options, 'applications', []))
            ->setEnabled($this->getOption($options, 'enabled', true))
            ->setOrder($this->getOption($options, 'order', 0))
            ->setFormType($this->getOption($options, 'form_type', ActionType::NAME))
            ->setButtonOptions($this->getOption($options, 'button_options', []))
            ->setFrontendOptions($this->getOption($options, 'frontend_options', []))
            ->setDatagridOptions($this->getOption($options, 'datagrid_options', []))
            ->setAttributes($this->getOption($options, 'attributes', []))
            ->setFormOptions($this->getOption($options, 'form_options', []));

        foreach (ActionDefinition::getAllowedConditions() as $name) {
            $actionDefinition->setConditions($name, $this->getOption($options, $name, []));
        }

        foreach (ActionDefinition::getAllowedFunctions() as $name) {
            $actionDefinition->setFunctions($name, $this->getOption($options, $name, []));
        }

        $this->addAclPrecondition($actionDefinition, $this->getOption($options, 'acl_resource'));

        return $actionDefinition;
    }

    /**
     * @param ActionDefinition $actionDefinition
     * @param mixed $aclResource
     */
    protected function addAclPrecondition(ActionDefinition $actionDefinition, $aclResource)
    {
        if (!$aclResource) {
            return;
        }

        $definition = $actionDefinition->getConditions(ActionDefinition::PRECONDITIONS);

        $newDefinition = ['@and' => [['@acl_granted' => $aclResource]]];
        if ($definition) {
            $newDefinition['@and'][] = $definition;
        }

        $actionDefinition->setConditions(ActionDefinition::PRECONDITIONS, $newDefinition);
    }

    /**
     * @param array $entities
     * @return array
     */
    protected function filterEntities(array $entities)
    {
        return array_filter(array_map([$this, 'getEntityClassName'], $entities), 'is_string');
    }

    /**
     * @param string $entityName
     * @return string|bool
     */
    protected function getEntityClassName($entityName)
    {
        if (!array_key_exists($entityName, $this->entityNames)) {
            $this->entityNames[$entityName] = null;

            try {
                $entityClass = $this->doctrineHelper->getEntityClass($entityName);

                if (class_exists($entityClass, true)) {
                    $this->entityNames[$entityName] = ltrim($entityClass, '\\');
                }
            } catch (ORMException $e) {
            }
        }

        return $this->entityNames[$entityName];
    }
}
