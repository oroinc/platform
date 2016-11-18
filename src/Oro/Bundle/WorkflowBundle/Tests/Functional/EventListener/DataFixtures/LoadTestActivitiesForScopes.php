<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\EventListener\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\TestFrameworkBundle\Entity\TestActivity;

class LoadTestActivitiesForScopes extends AbstractFixture
{
    /**
     * @var array
     */
    protected static $activities = [
        1 => ['message' => 'message 1'],
        2 => ['message' => 'message 2'],
        3 => ['message' => 'message 3'],
        4 => ['message' => 'message 4'],
    ];

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        foreach (self::$activities as $id => $activity) {
            $entity = new TestActivity();
            $entity->setMessage($activity['message']);
            $entity->setId($id);

            $manager->persist($entity);

            $this->addReference('test_activity_' . $id, $entity);
        }

        $manager->flush();
    }
}
