<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Handler\Helper;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowEntityAcl;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowRestriction;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;
use Oro\Bundle\WorkflowBundle\Handler\Helper\WorkflowDefinitionCloner;

class WorkflowDefinitionClonerTest extends \PHPUnit_Framework_TestCase
{
    public function testCloneDefinition()
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
        $definition->setSteps($steps)->setStartStep($step2)->setEntityAcls($entityAcls)->setRestrictions($restrictions);

        $newDefinition = WorkflowDefinitionCloner::cloneDefinition($definition);

        $this->assertEquals($definition, $newDefinition);
        $this->assertNotSame($definition, $newDefinition);
    }
}
