<?php

namespace Oro\Bundle\ActivityListBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\TestFrameworkBundle\Entity\TestActivity;
use Oro\Bundle\TestFrameworkBundle\Entity\TestActivityTarget;
use Oro\Bundle\UserBundle\Entity\User;

class LoadActivityData extends AbstractFixture
{
    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var User $user */
        $user = $manager->getRepository('OroUserBundle:User')->findOneByUsername('admin');
        $organization = $manager->getRepository('OroOrganizationBundle:Organization')->getFirst();

        $testActivityTarget = new TestActivityTarget();
        $testActivity1 = new TestActivity();
        $testActivity2 = new TestActivity();
        $testActivity1
            ->setMessage('activity_test1')
            ->setDescription('activity_test1 description')
            ->addActivityTarget($testActivityTarget)
            ->setOrganization($organization)
            ->setOwner($user);
        $testActivity2
            ->setMessage('activity_test2')
            ->setDescription('activity_test2 description')
            ->addActivityTarget($testActivityTarget)
            ->setOrganization($organization)
            ->setOwner($user);
        $manager->persist($testActivity1);
        $manager->persist($testActivity2);
        $manager->persist($testActivityTarget);
        $manager->flush();
        $this->setReference('test_activity_target_1', $testActivityTarget);
        $this->setReference('test_activity_1', $testActivity1);
        $this->setReference('test_activity_2', $testActivity2);
    }
}
