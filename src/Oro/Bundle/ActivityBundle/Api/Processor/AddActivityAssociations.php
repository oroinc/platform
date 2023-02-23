<?php

namespace Oro\Bundle\ActivityBundle\Api\Processor;

use Oro\Bundle\ActivityBundle\Api\ActivityAssociationProvider;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\GetConfig\ConfigContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Adds an association with all activity target entities to all activity entities.
 * Adds associations with activity entities to all entities that are a target of these activities.
 */
class AddActivityAssociations implements ProcessorInterface
{
    private const ACTIVITY_TARGETS_ASSOCIATION_NAME = 'activityTargets';
    private const ACTIVITY_TARGETS_ASSOCIATION_DATA_TYPE = 'association:manyToMany:activity';

    private ActivityAssociationProvider $activityAssociationProvider;

    public function __construct(ActivityAssociationProvider $activityAssociationProvider)
    {
        $this->activityAssociationProvider = $activityAssociationProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var ConfigContext $context */

        $entityClass = $context->getClassName();

        if ($this->activityAssociationProvider->isActivityEntity($entityClass)) {
            $this->addActivityTargetsAssociation(
                $context->getResult(),
                $entityClass,
                self::ACTIVITY_TARGETS_ASSOCIATION_NAME
            );
        }

        $activityAssociations = $this->activityAssociationProvider->getActivityAssociations(
            $entityClass,
            $context->getVersion(),
            $context->getRequestType()
        );
        if ($activityAssociations) {
            $definition = $context->getResult();
            foreach ($activityAssociations as $associationName => $activityAssociation) {
                $this->addActivityAssociation(
                    $definition,
                    $entityClass,
                    $associationName,
                    $activityAssociation['className'],
                    $activityAssociation['associationName']
                );
            }
        }
    }

    private function addActivityTargetsAssociation(
        EntityDefinitionConfig $definition,
        string $entityClass,
        string $associationName
    ): void {
        if ($definition->hasField($associationName)) {
            $dataType = $definition->getField($associationName)->getDataType();
            if ($dataType && self::ACTIVITY_TARGETS_ASSOCIATION_DATA_TYPE !== $dataType) {
                throw new \RuntimeException(sprintf(
                    'The association "%s" cannot be added to "%s"'
                    . ' because an association with this name already exists.',
                    $associationName,
                    $entityClass
                ));
            }
        }

        $association = $definition->getOrAddField($associationName);
        $association->setDataType(self::ACTIVITY_TARGETS_ASSOCIATION_DATA_TYPE);
    }

    private function addActivityAssociation(
        EntityDefinitionConfig $definition,
        string $entityClass,
        string $associationName,
        string $activityEntityClass,
        string $activityAssociationName
    ): void {
        if ($definition->hasField($associationName)) {
            $dataType = $definition->getField($associationName)->getDataType();
            if ($dataType && 'unidirectionalAssociation:' . $activityAssociationName !== $dataType) {
                throw new \RuntimeException(sprintf(
                    'The activity association "%2$s" cannot be added to "%1$s"'
                    . ' because an association with this name already exists.'
                    . ' To rename the association to the "%3$s" activity entity'
                    . ' use "oro_activity.api.activity_association_names" configuration option.'
                    . ' For example:%4$soro_activity:%4$s    api:%4$s        activity_association_names:%4$s'
                    . '            \'%3$s\': \'newName\'',
                    $entityClass,
                    $associationName,
                    $activityEntityClass,
                    "\n"
                ));
            }
        }

        $association = $definition->getOrAddField($associationName);
        $association->setTargetClass($activityEntityClass);
        $association->setDataType('unidirectionalAssociation:' . $activityAssociationName);
    }
}
