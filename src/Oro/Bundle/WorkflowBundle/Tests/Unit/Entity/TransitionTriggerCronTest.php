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

    /**
     * {@inheritdoc}
     */
    protected function getEntity()
    {
        return new TransitionTriggerCron();
    }
}
