<?php

namespace Oro\Bundle\DataGridBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\DataGridBundle\Entity\AbstractGridView;
use Oro\Bundle\DataGridBundle\Entity\GridView;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadUser;
use Oro\Bundle\UserBundle\Entity\AbstractUser;

class LoadGridViewData extends AbstractFixture implements DependentFixtureInterface
{
    public const GRID_VIEW_1 = 'grid_view.1';
    public const GRID_VIEW_2 = 'grid_view.2';
    public const GRID_VIEW_3 = 'grid_view.3';

    protected static array $data = [
        self::GRID_VIEW_1 => [
            'name' => 'gridView',
            'type' => GridView::TYPE_PRIVATE,
            'gridName' => 'testing-grid',
            'owner' => LoadUserData::SIMPLE_USER,
        ],
        self::GRID_VIEW_2 => [
            'name' => 'gridView',
            'type' => GridView::TYPE_PUBLIC,
            'gridName' => 'testing-grid',
            'owner' => LoadUserData::SIMPLE_USER,
        ],
        self::GRID_VIEW_3 => [
            'name' => 'gridView',
            'type' => GridView::TYPE_PUBLIC,
            'gridName' => 'testing-grid',
            'owner' => LoadUser::USER,
        ]
    ];

    #[\Override]
    public function getDependencies(): array
    {
        return [LoadUserData::class, LoadUser::class];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        foreach (static::$data as $name => $data) {
            /** @var AbstractUser $user */
            $user = $this->getReference($data['owner']);
            $view = $this->createInstance();
            $view->setName($data['name']);
            $view->setType($data['type']);
            $view->setGridName($data['gridName']);
            $view->setOwner($user);
            $view->setOrganization($user->getOrganization());
            $manager->persist($view);
            $this->setReference($name, $view);
        }
        $manager->flush();
    }

    protected function createInstance(): AbstractGridView
    {
        return new GridView();
    }
}
