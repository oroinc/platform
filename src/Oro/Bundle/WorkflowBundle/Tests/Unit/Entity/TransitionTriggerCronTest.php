<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Entity;

use Oro\Bundle\WorkflowBundle\Entity\TransitionTriggerCron;

class TransitionTriggerCronTest extends AbstractTransitionTriggerTestCase
{
    public function testAccessors()
    {
        parent::testAccessors();

        $this->assertPropertyAccessors($this->entity, [
            ['cron', 'test_cron'],
            ['filter', 'test_filter'],
        ]);
    }

    public function testImport()
    {
        $trigger = $this->getEntity();
        /** @var TransitionTriggerCron $entity */
        $entity = $this->entity;
        $this->setDataToTrigger($trigger);
        $trigger->setCron('test_cron')
            ->setFilter('test_filter');
        $entity->import($trigger);
        $this->assertImportData();
        $this->assertEquals($trigger->getCron(), $entity->getCron());
        $this->assertEquals($trigger->getFilter(), $entity->getFilter());
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntity()
    {
        return new TransitionTriggerCron();
    }
}
