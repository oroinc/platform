<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;

class LoadEmailTemplateData extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
{
    /** @var ContainerInterface */
    private $container;

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return ['Oro\Bundle\EmailBundle\Tests\Functional\DataFixtures\LoadUserData'];
    }

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $owner = $this->getReference('simple_user');
        $calendar = $manager
            ->getRepository('Oro\Bundle\CalendarBundle\Entity\Calendar')
            ->findOneBy([]);

        $event = new CalendarEvent();
        $event->setTitle('test_title')
            ->setCalendar($calendar)
            ->setAllDay(true)
            ->setStart(new \DateTime('now -2 days', new \DateTimeZone('UTC')))
            ->setEnd(new \DateTime('now', new \DateTimeZone('UTC')));

        $emailTemplate1 = new EmailTemplate('no_entity_name', 'test {{ system.appFullName }} etc');
        $emailTemplate1->setOrganization($owner->getOrganization());
        $emailTemplate2 = new EmailTemplate('test_template', 'test {{ system.appFullName }} etc');
        $emailTemplate2->setEntityName('Oro\Bundle\CalendarBundle\Entity\CalendarEvent');
        $emailTemplate2->setOrganization($owner->getOrganization());

        $emailTemplate3 = new EmailTemplate('no_system', 'test {{ system.appFullName }} etc');
        $emailTemplate3->setIsSystem(false);
        $emailTemplate3->setEntityName('Entity\Name');
        $emailTemplate3->setOrganization($owner->getOrganization());
        $emailTemplate4 = new EmailTemplate('system', 'test {{ system.appFullName }} etc');
        $emailTemplate4->setIsSystem(true);
        $emailTemplate4->setEntityName('Entity\Name');
        $emailTemplate4->setOrganization($owner->getOrganization());
        $emailTemplate5 = new EmailTemplate('no_system_no_entity', 'test {{ system.appFullName }} etc');
        $emailTemplate5->setIsSystem(false);
        $emailTemplate5->setOrganization($owner->getOrganization());

        $manager->persist($event);
        $manager->persist($emailTemplate1);
        $manager->persist($emailTemplate2);
        $manager->persist($emailTemplate3);
        $manager->persist($emailTemplate4);
        $manager->persist($emailTemplate5);
        $manager->flush();

        $this->setReference('emailTemplate1', $emailTemplate1);
        $this->setReference('emailTemplate2', $emailTemplate2);
        $this->setReference('emailTemplate3', $emailTemplate3);
        $this->setReference('emailTemplate4', $emailTemplate4);
        $this->setReference('emailTemplate5', $emailTemplate5);
    }
}
