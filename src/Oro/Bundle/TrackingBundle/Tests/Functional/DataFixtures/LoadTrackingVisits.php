<?php

namespace Oro\Bundle\TrackingBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\TrackingBundle\Entity\TrackingVisit;

class LoadTrackingVisits extends AbstractFixture implements DependentFixtureInterface
{
    const TRACKING_VISIT_1 = 'oro_tracking.visit1';
    const TRACKING_VISIT_2 = 'oro_tracking.visit2';

    /**
     * @var array
     */
    private $visits = [
        self::TRACKING_VISIT_1 => [
            'trackingWebsite' => LoadTrackingWebsites::TRACKING_WEBSITE,
            'visitorUid' => 'visitorUid1',
            'userIdentifier' => 'userIdentifier1',
            'firstActionTime' => '2012-12-12 00:00:00',
            'lastActionTime' => '2012-12-12 00:00:10'
        ],
        self::TRACKING_VISIT_2 => [
            'trackingWebsite' => LoadTrackingWebsites::TRACKING_WEBSITE,
            'visitorUid' => 'visitorUid2',
            'userIdentifier' => 'userIdentifier2',
            'firstActionTime' => '2012-12-12 23:59:00',
            'lastActionTime' => '2012-12-12 23:59:10'
        ]
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->visits as $reference => $item) {
            $visit = new TrackingVisit();
            $visit->setTrackingWebsite($this->getReference($item['trackingWebsite']));
            $visit->setVisitorUid($item['visitorUid']);
            $visit->setUserIdentifier($item['userIdentifier']);
            $visit->setFirstActionTime(new \DateTime($item['firstActionTime'], new \DateTimeZone('UTC')));
            $visit->setLastActionTime(new \DateTime($item['lastActionTime'], new \DateTimeZone('UTC')));

            $manager->persist($visit);
            $this->addReference($reference, $visit);
        }

        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadTrackingWebsites::class
        ];
    }
}
