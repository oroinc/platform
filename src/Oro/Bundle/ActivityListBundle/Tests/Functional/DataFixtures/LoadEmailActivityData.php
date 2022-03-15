<?php

namespace Oro\Bundle\ActivityListBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ActivityListBundle\Entity\ActivityList;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\AbstractFixture;

class LoadEmailActivityData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [LoadCommentData::class];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $activityList1 = new ActivityList();
        $activityList1->setRelatedActivityClass(Email::class);
        $activityList1->setRelatedActivityId($this->getReference('first_activity')->getId());
        $activityList1->setVerb('create');
        $activityList1->setSubject('test1');

        $activityList2 = new ActivityList();
        $activityList2->setRelatedActivityClass(Email::class);
        $activityList2->setRelatedActivityId($this->getReference('second_activity')->getId());
        $activityList2->setVerb('create');
        $activityList2->setSubject('test2');

        $activityList3 = new ActivityList();
        $activityList3->setRelatedActivityClass(Email::class);
        $activityList3->setRelatedActivityId($this->getReference('third_activity')->getId());
        $activityList3->setVerb('create');
        $activityList3->setSubject('test3');

        $manager->persist($activityList1);
        $manager->persist($activityList2);
        $manager->persist($activityList3);
        $manager->flush();

        $this->setReference('test_activity_list_1', $activityList1);
        $this->setReference('test_activity_list_2', $activityList2);
    }
}
