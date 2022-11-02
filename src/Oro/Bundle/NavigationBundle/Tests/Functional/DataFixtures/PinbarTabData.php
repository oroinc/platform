<?php

namespace Oro\Bundle\NavigationBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\NavigationBundle\Entity\PinbarTab;

class PinbarTabData extends AbstractFixture implements DependentFixtureInterface
{
    public const PINBAR_TAB_1 = 'pinbar_tab_1';
    public const PINBAR_TAB_2 = 'pinbar_tab_2';
    public const PINBAR_TAB_3 = 'pinbar_tab_3';

    /** @var array */
    private static $pinbarTabs = [
        self::PINBAR_TAB_1 => [
            'item' => NavigationItemData::NAVIGATION_ITEM_PINBAR_1,
            'title' => 'Configuration - System',
            'titleShort' => 'Configuration',
        ],
        self::PINBAR_TAB_2 => [
            'item' => NavigationItemData::NAVIGATION_ITEM_PINBAR_2,
            'title' => 'Users',
            'titleShort' => 'Users',
        ],
        self::PINBAR_TAB_3 => [
            'item' => NavigationItemData::NAVIGATION_ITEM_PINBAR_3,
            'title' => 'User - View',
            'titleShort' => 'User',
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            NavigationItemData::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach (self::$pinbarTabs as $key => $data) {
            $entity = new PinbarTab();
            $entity->setItem($this->getReference($data['item']));
            $entity->setTitle($data['title']);
            $entity->setTitleShort($data['titleShort']);

            $this->setReference($key, $entity);
            $manager->persist($entity);
        }

        $manager->flush();
    }
}
