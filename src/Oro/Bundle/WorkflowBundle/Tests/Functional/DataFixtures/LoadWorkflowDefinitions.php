<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;

class LoadWorkflowDefinitions extends AbstractFixture
{
    const FIRST = 'first_definition';
    const SECOND = 'second_definition';

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $definitionNames = array(self::FIRST, self::SECOND);
        $hasDefinitions = false;

        foreach ($definitionNames as $definitionName) {
            if ($manager->getRepository('OroWorkflowBundle:WorkflowDefinition')->find($definitionName)) {
                continue;
            }

            $step = new WorkflowStep();
            $step->setName($definitionName . '_step')
                ->setLabel($definitionName . '_step');

            $definition = new WorkflowDefinition();
            $definition->setName($definitionName)
                ->setLabel($definitionName)
                ->setRelatedEntity('Oro\Bundle\TestFrameworkBundle\Entity\WorkflowAwareEntity')
                ->setEntityAttributeName('entity')
                ->addStep($step)
                ->setStartStep($step);

            $manager->persist($definition);
            $hasDefinitions = true;
        }

        if ($hasDefinitions) {
            $manager->flush();
        }
    }
}
