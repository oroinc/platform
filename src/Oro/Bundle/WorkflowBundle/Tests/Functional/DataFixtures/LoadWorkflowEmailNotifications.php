<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\NotificationBundle\Entity\EmailNotification;
use Oro\Bundle\NotificationBundle\Entity\Event;
use Oro\Bundle\NotificationBundle\Entity\RecipientList;
use Oro\Bundle\TestFrameworkBundle\Entity\WorkflowAwareEntity;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Migrations\Data\ORM\LoadWorkflowNotificationEvents;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class LoadWorkflowEmailNotifications extends AbstractFixture implements
    DependentFixtureInterface,
    ContainerAwareInterface
{
    use ContainerAwareTrait;

    const EMAIL_NOTIFICATION_NAME = 'wfa_email_notification';

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var Event $event */
        $event = $manager->getRepository(Event::class)
            ->findOneBy(['name' => LoadWorkflowNotificationEvents::TRANSIT_EVENT]);

        /** @var WorkflowDefinition $workflowDefinition */
        $workflowDefinition = $this->getReference('workflow.test_multistep_flow');

        $workflow = $this->container->get('oro_workflow.manager')->getWorkflow($workflowDefinition->getName());

        /** @var EmailTemplate $template */
        $template = $this->getReference(LoadWorkflowEmailTemplates::WFA_EMAIL_TEMPLATE_NAME);

        foreach ($workflow->getTransitionManager()->getTransitions() as $transition) {
            $recipientList = new RecipientList();
            $recipientList->setEmail('admin@example.com');

            $manager->persist($recipientList);

            $entity = new EmailNotification();
            $entity->setEntityName(WorkflowAwareEntity::class)
                ->setEvent($event)
                ->setTemplate($template)
                ->setRecipientList($recipientList)
                ->setWorkflowDefinition($workflowDefinition)
                ->setWorkflowTransitionName($transition->getName());

            $manager->persist($entity);
        }

        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [LoadWorkflowDefinitions::class, LoadWorkflowEmailTemplates::class];
    }
}
