<?php

namespace Oro\Bundle\ActivityListBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\TestFrameworkBundle\Entity\TestActivity;
use Oro\Bundle\TestFrameworkBundle\Entity\TestActivityTarget;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadUser;

class LoadActivityData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * {@inheritDoc}
     */
    public function getDependencies(): array
    {
        return [LoadOrganization::class, LoadUser::class];
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager): void
    {
        $activityTarget = new TestActivityTarget();
        $manager->persist($activityTarget);
        $this->setReference('test_activity_target_1', $activityTarget);

        $activity1 = new TestActivity();
        $activity1->setMessage('activity_test1');
        $activity1->setDescription('activity_test1 description');
        $activity1->addActivityTarget($activityTarget);
        $activity1->setOrganization($this->getReference(LoadOrganization::ORGANIZATION));
        $activity1->setOwner($this->getReference(LoadUser::USER));
        $manager->persist($activity1);
        $this->setReference('test_activity_1', $activity1);

        $activity2 = new TestActivity();
        $activity2->setMessage('activity_test2');
        $activity2->setDescription('activity_test2 description');
        $activity2->addActivityTarget($activityTarget);
        $activity2->setOrganization($this->getReference(LoadOrganization::ORGANIZATION));
        $activity2->setOwner($this->getReference(LoadUser::USER));
        $manager->persist($activity2);
        $this->setReference('test_activity_2', $activity2);

        $manager->flush();
    }
}
