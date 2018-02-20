<?php

namespace Oro\Bundle\WorkflowBundle\Handler\Helper;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowEntityAcl;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowRestriction;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;
use Oro\Bundle\WorkflowBundle\Helper\WorkflowDefinitionClonerHelper as WDCHelper;

class WorkflowDefinitionCloner
{
    /**
     * @param WorkflowDefinition $definition
     * @param WorkflowDefinition $source
     */
    public static function mergeDefinition(WorkflowDefinition $definition, WorkflowDefinition $source)
    {
        self::copyMainFields($definition, $source)
            ->setSteps($source->getSteps())
            ->setStartStep($source->getStartStep())
            ->setEntityAcls($source->getEntityAcls())
            ->setRestrictions($source->getRestrictions());
    }

    /**
     * @param WorkflowDefinition $definition
     * @return WorkflowDefinition
     */
    public static function cloneDefinition(WorkflowDefinition $definition)
    {
        $steps = self::copySteps($definition->getSteps());

        $newDefinition = self::copyMainFields(new WorkflowDefinition(), $definition)->setSteps($steps);

        $startStep = $definition->getStartStep();
        $startStep = $steps->get($startStep ? $startStep->getName() : null);

        $entityAcls = self::copyEntityAcls($definition);
        $restrictions = self::copyRestrictions($definition);

        return $newDefinition->setStartStep($startStep)->setEntityAcls($entityAcls)->setRestrictions($restrictions);
    }

    /**
     * @param WorkflowDefinition $definition
     * @param WorkflowDefinition $source
     * @return WorkflowDefinition
     */
    private static function copyMainFields(WorkflowDefinition $definition, WorkflowDefinition $source)
    {
        $mergedConfiguration = WDCHelper::copyConfigurationVariables($definition, $source);

        $definition
            ->setName($source->getName())
            ->setLabel($source->getLabel())
            ->setRelatedEntity($source->getRelatedEntity())
            ->setEntityAttributeName($source->getEntityAttributeName())
            ->setConfiguration($mergedConfiguration)
            ->setStepsDisplayOrdered($source->isStepsDisplayOrdered())
            ->setSystem($source->isSystem())
            ->setPriority($source->getPriority())
            ->setExclusiveActiveGroups($source->getExclusiveActiveGroups())
            ->setExclusiveRecordGroups($source->getExclusiveRecordGroups())
            ->setApplications($source->getApplications());

        return $definition;
    }

    /**
     * @param Collection|WorkflowStep[] $steps
     * @return ArrayCollection
     */
    private static function copySteps(Collection $steps)
    {
        $newSteps = new ArrayCollection();
        foreach ($steps as $step) {
            $newStep = new WorkflowStep();
            $newStep->import($step);

            $newSteps->set($newStep->getName(), $newStep);
        }

        return $newSteps;
    }

    /**
     * @param WorkflowDefinition $definition
     * @return ArrayCollection
     */
    private static function copyEntityAcls(WorkflowDefinition $definition)
    {
        $newEntityAcls = new ArrayCollection();
        foreach ($definition->getEntityAcls() as $entityAcl) {
            $newEntityAcl = new WorkflowEntityAcl();
            $newEntityAcl->setDefinition($definition)->import($entityAcl);

            $newEntityAcls->add($newEntityAcl);
        }

        return $newEntityAcls;
    }

    /**
     * @param WorkflowDefinition $definition
     * @return ArrayCollection
     */
    private static function copyRestrictions(WorkflowDefinition $definition)
    {
        $newsRestrictions = new ArrayCollection();
        foreach ($definition->getRestrictions() as $restriction) {
            $newsRestriction = new WorkflowRestriction();
            $newsRestriction->setDefinition($definition)->import($restriction);

            $newsRestrictions->add($newsRestriction);
        }

        return $newsRestrictions;
    }
}
