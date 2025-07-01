<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Functional\Api\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Tests\Functional\Stub\ExternalFileFactoryStub;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\AbstractFixture;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadUser;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\File\File as ComponentFile;

class LoadFileData extends AbstractFixture implements DependentFixtureInterface
{
    #[\Override]
    public function getDependencies(): array
    {
        return [LoadUser::class, LoadOrganization::class];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $this->loadUsers($manager);

        /** @var User $user */
        $user = $this->getReference(LoadUser::USER);
        /** @var User $user1 */
        $user1 = $this->getReference('user_1');
        /** @var User $user2 */
        $user2 = $this->getReference('user_2');

        $file = new File();
        $file->setFile(new ComponentFile(__DIR__ . '/../../DataFixtures/files/file_1.txt'));
        $file->setOriginalFilename('file_1.txt');
        $file->setParentEntityFieldName('avatar');
        $file->setParentEntityClass(User::class);
        $file->setParentEntityId($user1->getId());
        $file->setOwner($user);
        $manager->persist($file);
        $this->setReference('file_1', $file);

        $file = new File();
        $file->setFilename('external_file');
        $file->setExternalUrl(ExternalFileFactoryStub::IMAGE_A_TEST_URL);
        $file->setOriginalFilename('file_1.txt');
        $file->setMimeType('text/plain');
        $file->setFileSize(7);
        $file->setParentEntityFieldName('avatar');
        $file->setParentEntityClass(User::class);
        $file->setParentEntityId($user2->getId());
        $file->setOwner($user);
        $manager->persist($file);
        $this->setReference('external_file_1', $file);

        $manager->flush();
    }

    public function loadUsers(ObjectManager $manager): void
    {
        $userManager = $this->container->get('oro_user.manager');
        /** @var Organization $organization */
        $organization = $this->getReference(LoadOrganization::ORGANIZATION);
        $role = $manager->getRepository(Role::class)->findOneBy(['role' => User::ROLE_DEFAULT]);

        $user1 = $userManager->createUser();
        $user1->setUsername('user_1')
            ->setPlainPassword('user_1')
            ->setEmail('user_1@example.com')
            ->setFirstName('Terry')
            ->setLastName('Johnson')
            ->setOrganization($organization)
            ->addOrganization($organization)
            ->addUserRole($role)
            ->setEnabled(true);
        $userManager->updateUser($user1);
        $this->setReference('user_1', $user1);

        $user2 = $userManager->createUser();
        $user2->setUsername('user_2')
            ->setPlainPassword('user_2')
            ->setEmail('user_2@example.com')
            ->setFirstName('Brandon')
            ->setLastName('Scott')
            ->setOrganization($organization)
            ->addOrganization($organization)
            ->addUserRole($role)
            ->setEnabled(true);
        $userManager->updateUser($user2);
        $this->setReference('user_2', $user2);
    }
}
