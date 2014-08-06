<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;

class LoadEmailTemplateData extends AbstractFixture implements ContainerAwareInterface
{
    /** @var ContainerInterface */
    private $container;

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
        $emailTemplate2 = new EmailTemplate('test_template', 'test {{ system.appFullName }} etc');
        $emailTemplate2->setEntityName('Oro\Bundle\CalendarBundle\Entity\CalendarEvent');

        $manager->persist($event);
        $manager->persist($emailTemplate1);
        $manager->persist($emailTemplate2);
        $manager->flush();
    }
}
