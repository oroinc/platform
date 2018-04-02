<?php

namespace Oro\Bundle\TestFrameworkBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\UserBundle\Entity;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Adds an avatar to the admin user data
 */
class AddAvatarToAdminUser extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
{
    use ContainerAwareTrait;

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
        /** @var Entity\UserManager $userManager */
        $userManager = $this->container->get('oro_user.manager');
        /** @var Entity\User $admin */
        $admin = $manager->getRepository('OroUserBundle:User')->findOneBy(['username' => 'admin']);

        if ($admin) {
            $this->addAvatarToUser($manager, $admin);
            $userManager->updateUser($admin);
        }
    }

    /**
     * @param ObjectManager $manager
     * @param Entity\User $adminUser
     */
    protected function addAvatarToUser(ObjectManager $manager, Entity\User $adminUser)
    {
        try {
            $imagePath = $this->container->get('file_locator')
                ->locate('@OroUIBundle/Resources/public/img//bg-login.jpg');

            if (is_array($imagePath)) {
                $imagePath = current($imagePath);
            }

            $image = $this->container->get('oro_attachment.file_manager')->createFileEntity($imagePath);
            $manager->persist($image);
            $adminUser->setAvatar($image);
        } catch (\InvalidArgumentException $e) {
            // Image not found
        }
    }
}
