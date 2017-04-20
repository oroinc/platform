<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\NotificationBundle\Entity\EmailNotification;
use Oro\Bundle\NotificationBundle\Entity\Event;
use Oro\Bundle\NotificationBundle\Entity\RecipientList;
use Oro\Bundle\NotificationBundle\Entity\Repository\EventRepository;
use Oro\Bundle\TestFrameworkBundle\Entity\WorkflowAwareEntity;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class LoadWorkflowEmailNotifications extends AbstractFixture implements
    DependentFixtureInterface,
    ContainerAwareInterface
{
    use ContainerAwareTrait;

    const EMAIL_NOTIFICATION_NAME = 'wfa_email_notification';
    const EVENT_NAME = 'oro.workflow.event.notification.workflow_transition';

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var EventRepository $repo */
        $repo = $manager->getRepository(Event::class);
        $event = $repo->findOneBy(['name' => self::EVENT_NAME]);
        /** @var WorkflowDefinition $workflowDefinition */
        $workflowDefinition = $this->getReference('workflow.test_multistep_flow');
        $workflow = $this->container->get('oro_workflow.manager')->getWorkflow($workflowDefinition->getName());

        foreach ($workflow->getTransitionManager()->getTransitions() as $transition) {
            $entity = new EmailNotification();
            $entity->setEntityName(WorkflowAwareEntity::class)
                ->setEvent($event)
                ->setTemplate($this->getReference(LoadWorkflowEmailTemplates::WFA_EMAIL_TEMPLATE_NAME))
                ->setRecipientList((new RecipientList())->setEmail('admin@example.com'));
            $entity->setWorkflowDefinition($workflowDefinition);
            $entity->setWorkflowTransitionName($transition->getName());

            $manager->persist($entity);
            $manager->flush();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getDependencies()
    {
        return [LoadWorkflowDefinitions::class, LoadWorkflowEmailTemplates::class];
    }
}
