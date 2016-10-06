<?php

namespace Oro\Bundle\WorkflowBundle\Handler\Helper;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowEntityAcl;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowRestriction;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;

class WorkflowDefinitionCloner
{
    /**
     * @param WorkflowDefinition $definition
     * @return WorkflowDefinition
     */
    public static function cloneDefinition(WorkflowDefinition $definition)
    {
        $startStep = $definition->getStartStep();

        $steps = self::copySteps($definition->getSteps());
        $startStep = $steps->get($startStep ? $startStep->getName() : null);
        $entityAcls = self::copyEntityAcls($definition->getEntityAcls());
        $restrictions = self::copyRestrictions($definition->getRestrictions());

        $newDefinition = new WorkflowDefinition();
        $newDefinition->import($definition)
            ->setSteps($steps)
            ->setStartStep($startStep)
            ->setEntityAcls($entityAcls)
            ->setRestrictions($restrictions);

        return $newDefinition;
    }

    /**
     * @param ArrayCollection|WorkflowStep[] $steps
     * @return ArrayCollection
     */
    protected static function copySteps(ArrayCollection $steps)
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
     * @param ArrayCollection|WorkflowEntityAcl[] $entityAcls
     * @return ArrayCollection
     */
    protected static function copyEntityAcls(ArrayCollection $entityAcls)
    {
        $newEntityAcls = new ArrayCollection();
        foreach ($entityAcls as $entityAcl) {
            $newEntityAcl = new WorkflowEntityAcl();
            $newEntityAcl->import($entityAcl);

            $newEntityAcls->add($newEntityAcl);
        }

        return $newEntityAcls;
    }

    /**
     * @param ArrayCollection|WorkflowRestriction[] $restrictions
     * @return ArrayCollection
     */
    protected static function copyRestrictions(ArrayCollection $restrictions)
    {
        $newsRestrictions = new ArrayCollection();
        foreach ($restrictions as $restriction) {
            $newsRestriction = new WorkflowRestriction();
            $newsRestriction->import($restriction);

            $newsRestrictions->add($newsRestriction);
        }

        return $restrictions;
    }
}
