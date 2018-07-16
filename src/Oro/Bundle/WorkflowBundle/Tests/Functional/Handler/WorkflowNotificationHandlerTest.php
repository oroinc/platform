<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\Handler;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\NotificationBundle\Entity\EmailNotification;
use Oro\Bundle\NotificationBundle\Provider\NotificationManager;
use Oro\Bundle\TestFrameworkBundle\Entity\WorkflowAwareEntity;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Event\WorkflowNotificationEvent;
use Oro\Bundle\WorkflowBundle\Handler\WorkflowNotificationHandler;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures\LoadWorkflowEmailNotifications;

class WorkflowNotificationHandlerTest extends WebTestCase
{
    const ENTITY_NAME = WorkflowAwareEntity::class;

    /** @var WorkflowNotificationHandler|\PHPUnit\Framework\MockObject\MockObject */
    protected $notificationHandler;

    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([LoadWorkflowEmailNotifications::class]);

        $this->getWorkflowManager()->activateWorkflow(
            $this->getWorkflow()->getName()
        );

        $this->notificationHandler = $this->createMock(WorkflowNotificationHandler::class);

        $this->replaceNotificationManager();
    }

    public function testNotificationOnAutoStartTransition()
    {
        $transitionName = '__start__';

        $this->assertNotificationHandlerCalled($transitionName);

        $this->createWorkflowAwareEntity();
    }

    public function testNotificationOnStartTransition()
    {
        $transitionName = 'starting_point_transition';

        $entity = $this->createWorkflowAwareEntity();

        $this->assertNotificationHandlerCalled($transitionName, $entity);

        $this->getWorkflowManager()->startWorkflow(
            $this->getWorkflow()->getName(),
            $entity,
            $transitionName
        );
    }

    public function testNotificationOnTransition()
    {
        $entity = $this->createWorkflowAwareEntity();

        $workflowItem = $this->getWorkflowManager()->startWorkflow(
            $this->getWorkflow()->getName(),
            $entity,
            'starting_point_transition'
        );

        $transitionName = 'second_point_transition';

        $this->assertNotificationHandlerCalled($transitionName, $entity);

        $this->getWorkflowManager()->transit(
            $workflowItem,
            'second_point_transition'
        );
    }

    /**
     * @return WorkflowAwareEntity
     */
    protected function createWorkflowAwareEntity()
    {
        $testEntity = new WorkflowAwareEntity();
        $testEntity->setName('test_' . uniqid('test', true));

        $manager = $this->getManager(WorkflowAwareEntity::class);
        $manager->persist($testEntity);
        $manager->flush($testEntity);

        return $testEntity;
    }

    /**
     * @param $class
     *
     * @return EntityManager|null|object
     */
    private function getManager($class)
    {
        return $this->getContainer()->get('doctrine')->getManagerForClass($class);
    }

    /**
     * @return object|WorkflowManager
     */
    private function getWorkflowManager()
    {
        return $this->getContainer()->get('oro_workflow.manager');
    }

    /**
     * @return WorkflowDefinition
     */
    private function getWorkflow()
    {
        return $this->getReference('workflow.test_multistep_flow');
    }

    private function replaceNotificationManager()
    {
        $manager = $this->getManager(EmailNotification::class);

        $notificationManager = new NotificationManager($manager, EmailNotification::class);
        $notificationManager->addHandler($this->notificationHandler);

        $this->getContainer()->set('oro_notification.manager', $notificationManager);
    }

    /**
     * @param string $transitionName
     * @param WorkflowAwareEntity $entity
     *
     * @throws \PHPUnit\Framework\MockObject\RuntimeException
     */
    private function assertNotificationHandlerCalled($transitionName, WorkflowAwareEntity $entity = null)
    {
        $this->notificationHandler->expects($this->once())->method('handle')->willReturnCallback(
            function (WorkflowNotificationEvent $event, Collection $notifications) use ($transitionName, $entity) {
                if ($entity) {
                    $this->assertEquals($entity, $event->getEntity());
                }
                $this->assertInstanceOf(WorkflowAwareEntity::class, $event->getEntity());
                $this->assertEquals($transitionName, $event->getTransitionRecord()->getTransitionName());
                $this->assertGreaterThanOrEqual(1, $notifications->count());

                return null;
            }
        );
    }
}
