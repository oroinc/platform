<?php

namespace Oro\Bundle\DataGridBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\DataGridBundle\Entity\AbstractGridViewUser;
use Oro\Bundle\DataGridBundle\Entity\GridView;
use Oro\Bundle\DataGridBundle\Entity\GridViewUser;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadUser;

class LoadGridViewUserData extends AbstractFixture implements DependentFixtureInterface
{
    public const GRID_VIEW_USER_1 = 'grid_view_user.1';
    public const GRID_VIEW_USER_2 = 'grid_view_user.2';
    public const GRID_VIEW_USER_3 = 'grid_view_user.3';
    public const GRID_VIEW_USER_4 = 'grid_view_user.4';

    protected static array $data = [
        self::GRID_VIEW_USER_1 => [
            'user' => LoadUserData::SIMPLE_USER,
            'gridView' => LoadGridViewData::GRID_VIEW_1
        ],
        self::GRID_VIEW_USER_2 => [
            'user' => LoadUserData::SIMPLE_USER_2,
            'gridView' => LoadGridViewData::GRID_VIEW_2
        ],
        self::GRID_VIEW_USER_3 => [
            'user' => LoadUser::USER,
            'gridView' => LoadGridViewData::GRID_VIEW_3
        ],
        self::GRID_VIEW_USER_4 => [
            'user' => LoadUserData::SIMPLE_USER_2,
            'gridView' => LoadGridViewData::GRID_VIEW_1
        ]
    ];

    #[\Override]
    public function getDependencies(): array
    {
        return [LoadGridViewData::class, LoadUserData::class, LoadUser::class];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        foreach (static::$data as $name => $data) {
            /** @var GridView $gridView */
            $gridView = $this->getReference($data['gridView']);
            $viewUser = $this->createInstance();
            $viewUser->setUser($this->getReference($data['user']));
            $viewUser->setGridView($gridView);
            $viewUser->setAlias($gridView->getName());
            $viewUser->setGridName($gridView->getGridName());
            $manager->persist($viewUser);
            $this->setReference($name, $viewUser);
        }
        $manager->flush();
    }

    protected function createInstance(): AbstractGridViewUser
    {
        return new GridViewUser();
    }
}
