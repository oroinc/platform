<?php

namespace Oro\Bundle\UserBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Tests\Functional\DataFixtures\LoadInvalidFileFixture;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadBusinessUnit;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadUser;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\HttpFoundation\File\File as SymfonyFile;

class LoadUsersWithAvatars extends AbstractFixture implements DependentFixtureInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function getDependencies(): array
    {
        return [
            LoadOrganization::class,
            LoadBusinessUnit::class,
            LoadUser::class,
            LoadInvalidFileFixture::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager): void
    {
        $user1 = $this->createUser($manager, 1, User::ROLE_ANONYMOUS);
        $user2 = $this->createUser($manager, 2, User::ROLE_DEFAULT);
        $user3 = $this->createUser($manager, 3, User::ROLE_DEFAULT);

        $manager->flush();

        $file2 = $this->createFile($manager, $user2->getId());
        $user2->setAvatar($file2);

        $user3->setAvatar($this->getReference(LoadInvalidFileFixture::INVALID_FILE_1));

        $manager->flush();
    }

    private function createFile(ObjectManager $manager, int $userNumber): File
    {
        $file = new File();
        $file->setFile(new SymfonyFile(__DIR__ . '/files/empty.jpg'));
        $file->setOriginalFilename('empty.jpg');
        $file->setFilename('empty.jpg');
        $manager->persist($file);

        $this->setReference(sprintf('user_%d_avatar', $userNumber), $file);

        return $file;
    }

    private function createUser(ObjectManager $manager, int $number, string $role): User
    {
        $userManager = $this->container->get('oro_user.manager');
        $organization = $this->getReference('organization');
        $businessUnit = $this->getReference('business_unit');
        $role = $manager->getRepository(Role::class)->findOneBy(['role' => $role]);

        $username = sprintf('user%d', $number);

        /** @var User $user */
        $user = $userManager->createUser();
        $user->setUsername($username)
            ->setOwner($businessUnit)
            ->setPlainPassword('sample_password')
            ->setFirstName($username)
            ->setLastName($username)
            ->setEmail(sprintf('%s@example.org', $username))
            ->setOrganization($organization)
            ->addOrganization($organization)
            ->addUserRole($role)
            ->setEnabled(true);
        $userManager->updateUser($user);

        $this->setReference($username, $user);

        return $user;
    }
}
