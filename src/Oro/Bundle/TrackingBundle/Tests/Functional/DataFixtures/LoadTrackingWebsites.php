<?php

namespace Oro\Bundle\TrackingBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\TrackingBundle\Entity\TrackingWebsite;
use Oro\Bundle\UserBundle\Entity\User;

class LoadTrackingWebsites extends AbstractFixture
{
    const TRACKING_WEBSITE = 'oro_tracking.website1';

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var User $user */
        $user = $manager->getRepository(User::class)->findOneByUsername('admin');
        /** @var Organization $organization */
        $organization = $manager->getRepository(Organization::class)->getFirst();

        $website = new TrackingWebsite();
        $website->setIdentifier(self::TRACKING_WEBSITE);
        $website->setName(self::TRACKING_WEBSITE);
        $website->setOrganization($organization);
        $website->setOwner($user);
        $website->setUrl('http://localhost');

        $manager->persist($website);
        $manager->flush($website);

        $this->setReference(self::TRACKING_WEBSITE, $website);
    }
}
