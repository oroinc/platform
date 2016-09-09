<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Entity;

use Oro\Bundle\WorkflowBundle\Entity\TransitionTriggerEvent;

class TransitionTriggerEventTest extends AbstractTransitionTriggerTestCase
{
    public function testAccessors()
    {
        parent::testAccessors();

        $this->assertPropertyAccessors($this->entity, [
            ['event', 'test_event'],
            ['field', 'test_field'],
            ['require', 'test_require'],
            ['relation', 'test_relation'],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntity()
    {
        return new TransitionTriggerEvent();
    }
}
