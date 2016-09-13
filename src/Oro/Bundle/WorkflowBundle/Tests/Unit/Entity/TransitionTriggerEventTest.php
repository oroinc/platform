<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Entity;

use Oro\Bundle\WorkflowBundle\Entity\TransitionTriggerEvent;

class TransitionTriggerEventTest extends AbstractTransitionTriggerTestCase
{
    public function testAccessors()
    {
        parent::testAccessors();

        $this->assertPropertyAccessors($this->entity, [
            ['entityClass', 'test_entity'],
            ['event', 'test_event'],
            ['field', 'test_field'],
            ['require', 'test_require'],
            ['relation', 'test_relation'],
        ]);
    }

    public function testImport()
    {
        $trigger = $this->getEntity();
        /** @var TransitionTriggerEvent $entity */
        $entity = $this->entity;
        $this->setDataToTrigger($trigger);
        $trigger->setEvent('test_event')
            ->setEntityClass('test_entity')
            ->setRelation('test_relation')
            ->setRequire('test_require')
            ->setField('test_field');
        $entity->import($trigger);
        $this->assertImportData();
        $this->assertEquals($trigger->getEvent(), $entity->getEvent());
        $this->assertEquals($trigger->getEntityClass(), $entity->getEntityClass());
        $this->assertEquals($trigger->getRelation(), $entity->getRelation());
        $this->assertEquals($trigger->getRequire(), $entity->getRequire());
        $this->assertEquals($trigger->getField(), $entity->getField());
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntity()
    {
        return new TransitionTriggerEvent();
    }
}
