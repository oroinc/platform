<?php

namespace Oro\Bundle\TrackingBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TrackingBundle\Entity\Repository\UniqueTrackingVisitRepository;
use Oro\Bundle\TrackingBundle\Entity\TrackingVisit;
use Oro\Bundle\TrackingBundle\Entity\UniqueTrackingVisit;
use Oro\Bundle\TrackingBundle\Tests\Functional\DataFixtures\LoadTrackingVisits;
use Oro\Bundle\TrackingBundle\Tests\Functional\DataFixtures\LoadTrackingWebsites;

/**
 * @dbIsolation
 */
class UniqueTrackingVisitRepositoryTest extends WebTestCase
{
    /**
     * @var UniqueTrackingVisitRepository
     */
    private $repository;

    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([
            LoadTrackingVisits::class
        ]);
        $this->repository = $this->getContainer()->get('doctrine')
            ->getManagerForClass(UniqueTrackingVisit::class)
            ->getRepository(UniqueTrackingVisit::class);
    }

    public function testGetUniqueRecordByTrackingVisit()
    {
        /** @var TrackingVisit $visit */
        $visit = $this->getReference(LoadTrackingVisits::TRACKING_VISIT_1);

        $timezone = $this->getTimezone();

        $uniqueVisit = $this->repository->getUniqueRecordByTrackingVisit($visit, $timezone);
        $this->assertInstanceOf(UniqueTrackingVisit::class, $uniqueVisit);
        $this->assertSame(1, $uniqueVisit->getVisitCount());

        $this->assertUniqueVisitMatchesVisit($visit, $uniqueVisit, $timezone);
    }

    public function testLogTrackingVisitNew()
    {
        $newVisit = new TrackingVisit();
        $newVisit->setTrackingWebsite($this->getReference(LoadTrackingWebsites::TRACKING_WEBSITE));
        $newVisit->setUserIdentifier('new_user');
        $newVisit->setFirstActionTime(new \DateTime('2011-11-11 11:11:11', new \DateTimeZone('UTC')));

        $timezone = $this->getTimezone();
        $loggedUniqueVisit = $this->repository->logTrackingVisit($newVisit, $timezone);
        $this->assertInstanceOf(UniqueTrackingVisit::class, $loggedUniqueVisit);
        $this->assertSame(1, $loggedUniqueVisit->getVisitCount());
        $this->assertUniqueVisitMatchesVisit($newVisit, $loggedUniqueVisit, $timezone);
    }

    public function testLogTrackingVisitExisting()
    {
        /** @var TrackingVisit $visit */
        $visit = $this->getReference(LoadTrackingVisits::TRACKING_VISIT_2);

        $timezone = $this->getTimezone();
        $loggedUniqueVisit = $this->repository->logTrackingVisit($visit, $timezone);
        $this->assertInstanceOf(UniqueTrackingVisit::class, $loggedUniqueVisit);
        $this->assertSame(2, $loggedUniqueVisit->getVisitCount());
        $this->assertUniqueVisitMatchesVisit($visit, $loggedUniqueVisit, $timezone);
    }

    /**
     * @return \DateTimeZone
     */
    private function getTimezone()
    {
        $configManager = $this->getContainer()->get('oro_config.global');

        $timezoneName = $configManager->get('oro_locale.timezone');
        if (!$timezoneName) {
            $timezoneName = 'UTC';
        }
        return new \DateTimeZone($timezoneName);
    }

    /**
     * @param TrackingVisit $visit
     * @param UniqueTrackingVisit $uniqueVisit
     * @param \DateTimeZone $timezone
     */
    private function assertUniqueVisitMatchesVisit(
        TrackingVisit $visit,
        UniqueTrackingVisit $uniqueVisit,
        \DateTimeZone $timezone
    ) {
        $this->assertSame($visit->getTrackingWebsite(), $uniqueVisit->getTrackingWebsite());
        $this->assertSame(md5($visit->getUserIdentifier()), $uniqueVisit->getUserIdentifier());
        $visitDate = clone $visit->getFirstActionTime();
        $visitDate->setTimezone($timezone);
        $this->assertEquals($visitDate->format('Y-m-d'), $uniqueVisit->getFirstActionTime()->format('Y-m-d'));
    }
}
