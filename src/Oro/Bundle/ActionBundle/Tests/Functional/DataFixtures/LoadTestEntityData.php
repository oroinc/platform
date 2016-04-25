<?php

namespace Oro\Bundle\ActionBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\TestFrameworkBundle\Entity\TestActivity;

class LoadTestEntityData extends AbstractFixture
{
    const TEST_ENTITY_1 = 'test_entity_1';
    const TEST_ENTITY_2 = 'test_entity_2';

    /**
     * @var array
     */
    protected $activities = [
        self::TEST_ENTITY_1 => ['message' => 'test message', 'description' => null],
        self::TEST_ENTITY_2 => ['message' => 'new message', 'description' => 'Test Description']
    ];

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->activities as $name => $activity) {
            $entity = new TestActivity();
            $entity->setMessage($activity['message'])->setDescription($activity['description']);

            $manager->persist($entity);

            $this->addReference($name, $entity);
        }

        $manager->flush();
    }
}
