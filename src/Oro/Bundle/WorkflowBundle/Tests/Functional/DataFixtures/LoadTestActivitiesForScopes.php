<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\TestFrameworkBundle\Entity\TestActivity;

class LoadTestActivitiesForScopes extends AbstractFixture
{
    const TEST_ACTIVITY_1 = 'test_activity_1';
    const TEST_ACTIVITY_2 = 'test_activity_2';
    const TEST_ACTIVITY_3 = 'test_activity_3';
    const TEST_ACTIVITY_4 = 'test_activity_4';

    /**
     * @var array
     */
    protected static $activities = [
        self::TEST_ACTIVITY_1 => ['message' => 'message 1'],
        self::TEST_ACTIVITY_2 => ['message' => 'message 2'],
        self::TEST_ACTIVITY_3 => ['message' => 'message 3'],
        self::TEST_ACTIVITY_4 => ['message' => 'message 4'],
    ];

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        foreach (self::$activities as $name => $activity) {
            $entity = new TestActivity();
            $entity->setMessage($activity['message']);

            $manager->persist($entity);

            $this->addReference($name, $entity);
        }

        $manager->flush();
    }
}
