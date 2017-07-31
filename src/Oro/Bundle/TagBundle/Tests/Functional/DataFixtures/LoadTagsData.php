<?php

namespace Oro\Bundle\TagBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\TagBundle\Entity\Tag;
use Oro\Bundle\TagBundle\Entity\Tagging;
use Oro\Bundle\TestFrameworkBundle\Entity\TestActivity;

class LoadTagsData extends AbstractFixture
{
    const FIRST_ACTIVITY = 'firstActivity';
    const SECOND_ACTIVITY = 'secondActivity';
    const THIRD_ACTIVITY = 'thirdActivity';

    const FIRST_TAG = 'firstTag';

    private static $activitiesTags = [
        self::FIRST_ACTIVITY => [
            self::FIRST_TAG
        ],
        self::SECOND_ACTIVITY => [
            self::FIRST_TAG
        ]
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->loadActivities($manager);
        $this->loadTags($manager);

        foreach (static::$activitiesTags as $activityReference => $tagReferences) {
            /** @var array $tagReferences */
            foreach ($tagReferences as $tagReference) {
                $tagging = new Tagging();

                /** @var Tag $tag */
                $tag = $this->getReference($tagReference);
                $tagging->setTag($tag);

                $this->addReference(sprintf('%s.%s', $activityReference, $tagReference), $tagging);

                /** @var TestActivity $activity */
                $activity = $this->getReference($activityReference);
                $tagging->setEntity($activity);

                $manager->persist($tagging);
            }
        }

        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     */
    private function loadActivities(ObjectManager $manager)
    {
        $activity = new TestActivity();
        $activity->setMessage(self::FIRST_ACTIVITY);
        $manager->persist($activity);
        $this->addReference(self::FIRST_ACTIVITY, $activity);

        $activity = new TestActivity();
        $activity->setMessage(self::SECOND_ACTIVITY);
        $manager->persist($activity);
        $this->addReference(self::SECOND_ACTIVITY, $activity);

        $activity = new TestActivity();
        $activity->setMessage(self::THIRD_ACTIVITY);
        $manager->persist($activity);
        $this->addReference(self::THIRD_ACTIVITY, $activity);

        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     */
    private function loadTags(ObjectManager $manager)
    {
        $tag = new Tag();
        $tag->setName(self::FIRST_TAG);

        $this->addReference(self::FIRST_TAG, $tag);

        $manager->persist($tag);
        $manager->flush();
    }
}
