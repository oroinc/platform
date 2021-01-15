<?php

namespace Oro\Bundle\NavigationBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\NavigationBundle\Entity\PageState;
use Oro\Bundle\UserBundle\DataFixtures\UserUtilityTrait;
use Oro\Bundle\UserBundle\Tests\Functional\DataFixtures\LoadUserData;

class PageStateData extends AbstractFixture implements DependentFixtureInterface
{
    use UserUtilityTrait;

    public const PAGE_STATE_1 = 'item_1';

    /** @var array */
    private static $pageStates = [
        self::PAGE_STATE_1 => [
            'user' => LoadUserData::SIMPLE_USER,
            'pageId' => 'sample-id',
            'data' => '{"sampleField": "sampleValue"}',
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadUserData::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach (self::$pageStates as $key => $data) {
            $entity = new PageState();
            $entity->setUser($this->getReference($data['user']));
            $entity->setPageId($data['pageId']);
            $entity->setData($data['data']);

            $this->setReference($key, $entity);
            $manager->persist($entity);
        }

        $manager->flush();
    }
}
