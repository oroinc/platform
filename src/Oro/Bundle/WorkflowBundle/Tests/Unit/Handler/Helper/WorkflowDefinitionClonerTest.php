<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Handler\Helper;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowEntityAcl;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowRestriction;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;
use Oro\Bundle\WorkflowBundle\Handler\Helper\WorkflowDefinitionCloner;

class WorkflowDefinitionClonerTest extends \PHPUnit\Framework\TestCase
{
    public function testCloneDefinition()
    {
        $definition = $this->createDefinition();

        $clonedDefinition = WorkflowDefinitionCloner::cloneDefinition($definition);

        $this->assertSameMainFields($definition, $clonedDefinition);

        $this->assertEquals($definition, $clonedDefinition);
        $this->assertNotSame($definition, $clonedDefinition);

        $this->assertEquals($definition->getStartStep(), $clonedDefinition->getStartStep());
        $this->assertNotSame($definition->getStartStep(), $clonedDefinition->getStartStep());

        $this->assertObjectsDefinitions($definition, $clonedDefinition, true);
    }

    public function testMergeDefinition()
    {
        $sourceDefinition = $this->createDefinition();

        $definition = new WorkflowDefinition();

        WorkflowDefinitionCloner::mergeDefinition($definition, $sourceDefinition);

        $this->assertSameMainFields($definition, $sourceDefinition);

        $this->assertEquals($definition, $sourceDefinition);
        $this->assertNotSame($definition, $sourceDefinition);

        $this->assertSame($definition->getStartStep(), $sourceDefinition->getStartStep());

        $this->assertObjectsDefinitions($definition, $sourceDefinition);
    }

    private function assertSameMainFields(WorkflowDefinition $definition1, WorkflowDefinition $definition2)
    {
        $this->assertSame($definition1->getName(), $definition2->getName());
        $this->assertSame($definition1->getLabel(), $definition2->getLabel());
        $this->assertSame($definition1->getRelatedEntity(), $definition2->getRelatedEntity());
        $this->assertSame($definition1->getEntityAttributeName(), $definition2->getEntityAttributeName());
        $this->assertSame($definition1->getConfiguration(), $definition2->getConfiguration());
        $this->assertSame($definition1->isStepsDisplayOrdered(), $definition2->isStepsDisplayOrdered());
        $this->assertSame($definition1->isSystem(), $definition2->isSystem());
        $this->assertSame($definition1->getPriority(), $definition2->getPriority());
        $this->assertSame($definition1->getScopesConfig(), $definition2->getScopesConfig());
        $this->assertSame($definition1->getExclusiveActiveGroups(), $definition2->getExclusiveActiveGroups());
        $this->assertSame($definition1->getExclusiveRecordGroups(), $definition2->getExclusiveRecordGroups());
        $this->assertSame($definition1->getMetadata(), $definition2->getMetadata());
    }

    /**
     * @param WorkflowDefinition $definition1
     * @param WorkflowDefinition $definition2
     * @param bool               $isCopy
     */
    private function assertObjectsDefinitions(
        WorkflowDefinition $definition1,
        WorkflowDefinition $definition2,
        $isCopy = false
    ) {
        if ($isCopy) {
            $testDefinition = $definition2;
        } else {
            $testDefinition = $definition1;
        }

        foreach ($definition1->getSteps() as $item) {
            $this->assertSame($definition1, $item->getDefinition());
        }
        foreach ($definition2->getSteps() as $item) {
            $this->assertSame($testDefinition, $item->getDefinition());
        }

        foreach ($definition1->getEntityAcls() as $item) {
            $this->assertSame($definition1, $item->getDefinition());
        }
        foreach ($definition2->getEntityAcls() as $item) {
            $this->assertSame($testDefinition, $item->getDefinition());
        }

        foreach ($definition1->getRestrictions() as $item) {
            $this->assertSame($definition1, $item->getDefinition());
        }
        foreach ($definition2->getRestrictions() as $item) {
            $this->assertSame($testDefinition, $item->getDefinition());
        }
    }

    /**
     * @return WorkflowDefinition
     */
    private function createDefinition()
    {
        $step1 = new WorkflowStep();
        $step1->setName('step1');

        $step2 = new WorkflowStep();
        $step2->setName('step2');

        $steps = new ArrayCollection([$step1, $step2]);

        $entityAcl1 = new WorkflowEntityAcl();
        $entityAcl1->setStep($step1);

        $entityAcl2 = new WorkflowEntityAcl();
        $entityAcl2->setStep($step2);

        $entityAcls = new ArrayCollection([$entityAcl1, $entityAcl2]);

        $restriction1 = new WorkflowRestriction();
        $restriction1->setStep($step1);

        $restriction2 = new WorkflowRestriction();
        $restriction2->setStep($step2);

        $restrictions = new ArrayCollection([$restriction1, $restriction2]);

        $definition = new WorkflowDefinition();
        $definition
            ->setSteps($steps)
            ->setStartStep($step2)
            ->setEntityAcls($entityAcls)
            ->setRestrictions($restrictions)
            ->setApplications(['app1', 'app2'])
            ->setMetadata(['test_key' => 'test_value']);

        return $definition;
    }
}
