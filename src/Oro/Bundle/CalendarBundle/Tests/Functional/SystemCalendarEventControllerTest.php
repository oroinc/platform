<?php

namespace Oro\Bundle\CalendarBundle\Tests\Functional;

use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\CalendarBundle\Entity\SystemCalendar;

/**
 * @outputBuffering enabled
 * @dbIsolation
 */
class SystemCalendarEventControllerTest extends WebTestCase
{
    /** @var ObjectManager */
    protected $em;

    protected function setUp()
    {
        $this->initClient(
            [],
            array_merge($this->generateBasicAuthHeader(), ['HTTP_X-CSRF-Header' => 1])
        );
        $this->loadFixtures(['Oro\Bundle\CalendarBundle\Tests\Functional\Fixtures\SystemCalendarFixtures']);
        $this->em = self::getContainer()->get('doctrine.orm.entity_manager');
    }

    public function testIndexSystemCalendarOwnOrganization()
    {
        /** @var SystemCalendar $systemCalendar */
        $systemCalendar = $this->em->getRepository('OroCalendarBundle:SystemCalendar')
            ->findOneBy(['name' => 'Own Calendar']);
        $this->client->request(
            'GET',
            $this->getUrl('oro_calendar_system_event_index', ['id' => $systemCalendar->getId()])
        );
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
    }

    public function testIndexSystemCalendarForeignOrganization()
    {
        /** @var SystemCalendar $systemCalendar */
        $systemCalendar = $this->em->getRepository('OroCalendarBundle:SystemCalendar')
            ->findOneBy(['name' => 'Foreign Calendar']);
        $this->client->request(
            'GET',
            $this->getUrl('oro_calendar_system_event_index', ['id' => $systemCalendar->getId()])
        );
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 403);
    }

    public function testIndexPublicCalendar()
    {
        /** @var SystemCalendar $publicCalendar */
        $publicCalendar = $this->em->getRepository('OroCalendarBundle:SystemCalendar')
            ->findOneBy(['name' => 'Public Calendar']);
        $this->client->request(
            'GET',
            $this->getUrl('oro_calendar_system_event_index', ['id' => $publicCalendar->getId()])
        );
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
    }
}
