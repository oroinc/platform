<?php

namespace Oro\Bundle\TestFrameworkBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Oro\Component\DependencyInjection\ContainerAwareInterface;
use Oro\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Adds an avatar to the admin user data
 */
class AddAvatarToAdminUser extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
{
    use ContainerAwareTrait;

    #[\Override]
    public function getDependencies(): array
    {
        return [
            LoadUserData::class,
        ];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        /** @var UserManager $userManager */
        $userManager = $this->container->get('oro_user.manager');
        /** @var User $admin */
        $admin = $manager->getRepository(User::class)->findOneBy(['username' => 'admin']);

        if ($admin) {
            $this->addAvatarToUser($manager, $admin);
            $userManager->updateUser($admin);
        }
    }

    private function addAvatarToUser(ObjectManager $manager, User $adminUser): void
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
