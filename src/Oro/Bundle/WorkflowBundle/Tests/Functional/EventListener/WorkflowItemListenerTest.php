<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\EventListener;

use Oro\Bundle\TestFrameworkBundle\Entity\WorkflowAwareEntity;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Tests\Functional\WorkflowTestCase;

class WorkflowItemListenerTest extends WorkflowTestCase
{
    protected function setUp()
    {
        $this->initClient([]);
    }

    public function testRemoveRelatedWorkflowItems()
    {
        self::loadWorkflowFrom('/Tests/Functional/EventListener/DataFixtures/config/ItemListenerRemoveItem');
        self::assertTrue(
            self::getSystemWorkflowRegistry()->hasActiveWorkflowsByEntityClass(WorkflowAwareEntity::class)
        );

        $entity = new WorkflowAwareEntity();
        $entity->setName('to remove');

        $em = self::getContainer()->get('oro_entity.doctrine_helper')->getEntityManager($entity);
        $em->persist($entity);
        $em->flush();

        self::assertInstanceOf(
            WorkflowItem::class,
            self::getSystemWorkflowManager()->getWorkflowItem($entity, 'test_flow_item_remove')
        );

        $em->remove($entity);
        $em->flush();

        self::assertNull(
            self::getSystemWorkflowManager()->getWorkflowItem($entity, 'test_flow_item_remove'),
            'WorkflowItem for related entity should be removed after entity removal.'
        );
    }
}
