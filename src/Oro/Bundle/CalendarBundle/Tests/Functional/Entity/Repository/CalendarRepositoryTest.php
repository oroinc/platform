<?php

namespace Oro\Bundle\CalendarBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\CalendarBundle\Entity\Repository\CalendarRepository;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class CalendarRepositoryTest extends WebTestCase
{
    /**
     * @var CalendarRepository
     */
    protected $repository;

    protected function setUp()
    {
        $this->initClient();
        $this->repository = $this->getContainer()->get('doctrine')->getRepository('OroCalendarBundle:Calendar');
    }

    public function testFindDefaultCalendars()
    {
        $doctrine = $this->getContainer()->get('doctrine');
        $userRepository = $doctrine->getRepository('OroUserBundle:User');
        $organizationRepository = $doctrine->getRepository('OroOrganizationBundle:Organization');

        $firstOrganization = $organizationRepository->getFirst();
        $firstOrganizationId = $firstOrganization->getId();

        $userIds = [];
        $expectedCalendars = [];

        $users = $userRepository->findBy(['organization' => $firstOrganization]);
        foreach ($users as $user) {
            $userId = $user->getId();
            $calendar = $this->repository->findDefaultCalendar($userId, $firstOrganizationId);
            if ($calendar) {
                $userIds[] = $userId;
                $expectedCalendars[] = $calendar;
            }
        }

        $this->assertEquals(
            $expectedCalendars,
            $this->repository->findDefaultCalendars($userIds, $firstOrganizationId)
        );
    }
}
