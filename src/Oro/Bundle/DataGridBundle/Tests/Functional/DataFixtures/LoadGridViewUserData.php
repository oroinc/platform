<?php

namespace Oro\Bundle\DataGridBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\DataGridBundle\Entity\GridView;
use Oro\Bundle\DataGridBundle\Entity\GridViewUser;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadAdminUserData;

class LoadGridViewUserData extends AbstractFixture implements DependentFixtureInterface
{
    const GRID_VIEW_USER_1 = 'grid_view_user.1';
    const GRID_VIEW_USER_2 = 'grid_view_user.2';
    const GRID_VIEW_USER_3 = 'grid_view_user.3';
    const GRID_VIEW_USER_4 = 'grid_view_user.4';

    /** @var array */
    protected static $data = [
        self::GRID_VIEW_USER_1 => [
            'user' => LoadUserData::SIMPLE_USER,
            'gridView' => LoadGridViewData::GRID_VIEW_1
        ],
        self::GRID_VIEW_USER_2 => [
            'user' => LoadUserData::SIMPLE_USER_2,
            'gridView' => LoadGridViewData::GRID_VIEW_2
        ],
        self::GRID_VIEW_USER_3 => [
            'user' => LoadAdminUserData::DEFAULT_ADMIN_USERNAME,
            'gridView' => LoadGridViewData::GRID_VIEW_3
        ],
        self::GRID_VIEW_USER_4 => [
            'user' => LoadUserData::SIMPLE_USER_2,
            'gridView' => LoadGridViewData::GRID_VIEW_1
        ]
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $userRepository = $manager->getRepository($this->getUserClassName());

        foreach (static::$data as $name => $data) {
            /** @var User $user */
            $user = $userRepository->findOneBy(['username' => $data['user']]);

            /** @var GridView $gridView */
            $gridView = $this->getReference($data['gridView']);

            $viewUser = $this->createInstance();
            $viewUser->setUser($user)
                ->setGridView($gridView)
                ->setAlias($gridView->getName())
                ->setGridName($gridView->getGridName());

            $manager->persist($viewUser);

            $this->setReference($name, $viewUser);
        }

        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [LoadGridViewData::class];
    }

    /**
     * @return GridViewUser
     */
    protected function createInstance()
    {
        return new GridViewUser();
    }

    /**
     * @return string
     */
    protected function getUserClassName()
    {
        return User::class;
    }
}
