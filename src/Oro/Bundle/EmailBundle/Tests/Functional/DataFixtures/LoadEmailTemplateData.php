<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

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


        $manager->persist($event);
        $manager->flush();

        $this->addReference('oro_email:', $event);
    }
}
