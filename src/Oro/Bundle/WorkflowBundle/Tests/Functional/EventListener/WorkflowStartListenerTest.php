<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\EventListener;

use Oro\Bundle\TestFrameworkBundle\Entity\WorkflowAwareEntity;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Tests\Functional\WorkflowTestCase;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class WorkflowStartListenerTest extends WorkflowTestCase
{
    /** @var \Doctrine\ORM\EntityManager */
    protected $entityManger;

    /** @var \Oro\Bundle\WorkflowBundle\Model\WorkflowManager */
    protected $systemWorkflowManager;

    protected function setUp()
    {
        $this->initClient([], self::generateBasicAuthHeader());
        $this->entityManger = $this->getEntityManager(WorkflowAwareEntity::class);
        $this->systemWorkflowManager = self::getSystemWorkflowManager();
    }

    public function testPersistStarts()
    {
        self::loadWorkflowFrom('/Tests/Functional/EventListener/DataFixtures/config/StartListenerActiveNotActive');

        $firstEntity = new WorkflowAwareEntity();
        $firstEntity->setName('first');

        $this->entityManger->persist($firstEntity);
        $this->entityManger->flush();

        self::assertInstanceOf(
            WorkflowItem::class,
            $this->systemWorkflowManager->getWorkflowItem($firstEntity, 'test_flow_autostart')
        );
        self::assertNull(
            $this->systemWorkflowManager->getWorkflowItem($firstEntity, 'test_flow_autostart_not_active')
        );

        //test after activation
        $this->systemWorkflowManager->activateWorkflow('test_flow_autostart_not_active');

        $secondEntity = new WorkflowAwareEntity();
        $secondEntity->setName('second');
        $this->entityManger->persist($secondEntity);
        $this->entityManger->flush();

        self::assertInstanceOf(
            WorkflowItem::class,
            $this->systemWorkflowManager->getWorkflowItem($secondEntity, 'test_flow_autostart')
        );
        self::assertInstanceOf(
            WorkflowItem::class,
            $this->systemWorkflowManager->getWorkflowItem($secondEntity, 'test_flow_autostart_not_active')
        );
    }

    public function testForceAutoStartDifferentApplications()
    {
        self::loadWorkflowFrom('/Tests/Functional/EventListener/DataFixtures/config/StartListenerForceAutoStart');

        //making context of execution as default application
        $user = self::getContainer()->get('doctrine')
            ->getRepository(User::class)
            ->findOneBy(['username' => 'admin']);
        $token = new UsernamePasswordToken($user, self::AUTH_PW, 'user');
        self::getContainer()->get('security.token_storage')->setToken($token);

        //testing in context of some request as application filters depends on requestStack
        self::getContainer()->get('event_dispatcher')
            ->addListener(KernelEvents::REQUEST, function () {
                $entity = new WorkflowAwareEntity();
                $entity->setName('entity');

                $this->entityManger->persist($entity);
                $this->entityManger->flush();

                //as default app
                self::assertInstanceOf(
                    WorkflowItem::class,
                    $this->systemWorkflowManager->getWorkflowItem($entity, 'test_flow_force_autostart_different_app')
                );

                self::assertInstanceOf(
                    WorkflowItem::class,
                    $this->systemWorkflowManager->getWorkflowItem($entity, 'test_flow_no_force_autostart_default_app')
                );

                self::assertNotInstanceOf(
                    WorkflowItem::class,
                    $this->systemWorkflowManager->getWorkflowItem($entity, 'test_flow_no_force_autostart_different_app')
                );
            });

        //open any page to fulfill requestStack
        $this->client->request('GET', $this->getUrl('oro_dashboard_index'));
    }

    /**
     * @param string $class
     * @return \Doctrine\ORM\EntityManager
     */
    protected function getEntityManager($class)
    {
        $doctrineHelper = self::getContainer()->get('oro_entity.doctrine_helper');

        return $doctrineHelper->getEntityManagerForClass($class);
    }
}
