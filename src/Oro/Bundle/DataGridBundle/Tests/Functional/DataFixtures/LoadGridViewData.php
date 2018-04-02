<?php

namespace Oro\Bundle\DataGridBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\DataGridBundle\Entity\AbstractGridView;
use Oro\Bundle\DataGridBundle\Entity\GridView;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadAdminUserData;

class LoadGridViewData extends AbstractFixture implements DependentFixtureInterface
{
    const GRID_VIEW_1 = 'grid_view.1';
    const GRID_VIEW_2 = 'grid_view.2';
    const GRID_VIEW_3 = 'grid_view.3';

    /** @var array */
    protected static $data = [
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
            'owner' => LoadAdminUserData::DEFAULT_ADMIN_USERNAME,
        ]
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $userRepository = $manager->getRepository($this->getUserClassName());

        foreach (static::$data as $name => $data) {
            $user = $userRepository->findOneBy(['username' => $data['owner']]);

            $view = $this->createInstance();
            $view->setName($data['name'])
                ->setType($data['type'])
                ->setGridName($data['gridName'])
                ->setOwner($user)
                ->setOrganization($user->getOrganization());

            $manager->persist($view);

            $this->setReference($name, $view);
        }

        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [LoadUserData::class];
    }

    /**
     * @return AbstractGridView
     */
    protected function createInstance()
    {
        return new GridView();
    }

    /**
     * @return string
     */
    protected function getUserClassName()
    {
        return User::class;
    }
}
