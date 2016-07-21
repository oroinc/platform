<?php

namespace Oro\Bundle\ActionBundle\Model\Assembler;

use Doctrine\ORM\ORMException;

use Oro\Bundle\ActionBundle\Form\Type\OperationType;
use Oro\Bundle\ActionBundle\Model\Operation;
use Oro\Bundle\ActionBundle\Model\OperationDefinition;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use Oro\Component\Action\Action\ActionFactory;
use Oro\Component\ConfigExpression\ExpressionFactory as ConditionFactory;

class OperationAssembler extends AbstractAssembler
{
    /** @var ActionFactory */
    private $actionFactory;

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
     * @param ActionFactory $actionFactory
     * @param ConditionFactory $conditionFactory
     * @param AttributeAssembler $attributeAssembler
     * @param FormOptionsAssembler $formOptionsAssembler
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(
        ActionFactory $actionFactory,
        ConditionFactory $conditionFactory,
        AttributeAssembler $attributeAssembler,
        FormOptionsAssembler $formOptionsAssembler,
        DoctrineHelper $doctrineHelper
    ) {
        $this->actionFactory = $actionFactory;
        $this->conditionFactory = $conditionFactory;
        $this->attributeAssembler = $attributeAssembler;
        $this->formOptionsAssembler = $formOptionsAssembler;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param array $configuration
     * @return Operation[]
     */
    public function assemble(array $configuration)
    {
        $operations = [];

        foreach ($configuration as $operationName => $options) {
            $operations[$operationName] = new Operation(
                $this->actionFactory,
                $this->conditionFactory,
                $this->attributeAssembler,
                $this->formOptionsAssembler,
                $this->assembleDefinition($operationName, $options)
            );
        }

        return $operations;
    }

    /**
     * @param string $operationName
     * @param array $options
     * @return OperationDefinition
     */
    protected function assembleDefinition($operationName, array $options)
    {
        $this->assertOptions($options, ['label'], $operationName);
        $operationDefinition = new OperationDefinition();

        $operationDefinition
            ->setName($operationName)
            ->setLabel($this->getOption($options, 'label'))
            ->setSubstituteOperation($this->getOption($options, 'substitute_operation', null))
            ->setForAllEntities($this->getOption($options, 'for_all_entities', false))
            ->setEntities($this->filterEntities($this->getOption($options, 'entities', [])))
            ->setExcludeEntities($this->filterEntities($this->getOption($options, 'exclude_entities', [])))
            ->setForAllDatagrids($this->getOption($options, 'for_all_datagrids', false))
            ->setDatagrids($this->getOption($options, 'datagrids', []))
            ->setExcludeDatagrids($this->getOption($options, 'exclude_datagrids', []))
            ->setRoutes($this->getOption($options, 'routes', []))
            ->setGroups($this->getOption($options, 'groups', []))
            ->setApplications($this->getOption($options, 'applications', []))
            ->setEnabled($this->getOption($options, 'enabled', true))
            ->setOrder($this->getOption($options, 'order', 0))
            ->setFormType($this->getOption($options, 'form_type', OperationType::NAME))
            ->setButtonOptions($this->getOption($options, 'button_options', []))
            ->setFrontendOptions($this->getOption($options, 'frontend_options', []))
            ->setDatagridOptions($this->getOption($options, 'datagrid_options', []))
            ->setAttributes($this->getOption($options, 'attributes', []))
            ->setFormOptions($this->getOption($options, 'form_options', []))
            ->setActionGroups($this->getOption($options, 'action_groups', []));

        foreach (OperationDefinition::getAllowedConditions() as $name) {
            $operationDefinition->setConditions($name, $this->getOption($options, $name, []));
        }

        foreach (OperationDefinition::getAllowedActions() as $name) {
            $operationDefinition->setActions($name, $this->getOption($options, $name, []));
        }

        $this->addAclPrecondition($operationDefinition, $this->getOption($options, 'acl_resource'));

        return $operationDefinition;
    }

    /**
     * @param OperationDefinition $operationDefinition
     * @param mixed $aclResource
     */
    protected function addAclPrecondition(OperationDefinition $operationDefinition, $aclResource)
    {
        if (!$aclResource) {
            return;
        }

        $aclDefinition = ['@acl_granted' => $aclResource];
        $definition = $operationDefinition->getConditions(OperationDefinition::PRECONDITIONS);
        if ($definition) {
            $newDefinition['@and'][] = $definition;
        }
        $newDefinition['@and'][] = $aclDefinition;

        $operationDefinition->setConditions(OperationDefinition::PRECONDITIONS, $newDefinition);
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
