<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Functional\Api\DataFixtures;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Entity\FileItem;
use Oro\Bundle\AttachmentBundle\Tests\Functional\Environment\Entity\TestAttachmentOwner;
use Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\AbstractFixture;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadUser;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\File\File as ComponentFile;

class LoadAttachmentOwnerData extends AbstractFixture implements DependentFixtureInterface
{
    #[\Override]
    public function getDependencies(): array
    {
        return [LoadUser::class];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $this->loadFiles($manager);
        $this->loadImages($manager);

        $entity = new TestAttachmentOwner();
        $entity->name = 'Entity 1';
        $entity->setTestFile($this->getReference('file_1'));
        $entity->setTestImage($this->getReference('image_1'));
        $entity->setTestMultiFiles(new ArrayCollection([
            $this->createFileItem($this->getReference('file_2'), 2),
            $this->createFileItem($this->getReference('file_3'), 1)
        ]));
        $entity->setTestMultiImages(new ArrayCollection([
            $this->createFileItem($this->getReference('image_2'), 2),
            $this->createFileItem($this->getReference('image_3'), 1)
        ]));
        $manager->persist($entity);
        $this->setReference('entity1', $entity);

        $manager->flush();
    }

    public function loadFiles(ObjectManager $manager): void
    {
        /** @var User $user */
        $user = $this->getReference(LoadUser::USER);

        $file = new File();
        $file->setFile(new ComponentFile(__DIR__ . '/../../DataFixtures/files/file_1.txt'));
        $file->setOriginalFilename('file_1.txt');
        $file->setOwner($user);
        $manager->persist($file);
        $this->setReference('file_1', $file);

        $file = new File();
        $file->setFile(new ComponentFile(__DIR__ . '/../../DataFixtures/files/file_1.txt'));
        $file->setOriginalFilename('file_2.txt');
        $file->setOwner($user);
        $manager->persist($file);
        $this->setReference('file_2', $file);

        $file = new File();
        $file->setFile(new ComponentFile(__DIR__ . '/../../DataFixtures/files/file_1.txt'));
        $file->setOriginalFilename('file_3.txt');
        $file->setOwner($user);
        $manager->persist($file);
        $this->setReference('file_3', $file);
    }

    public function loadImages(ObjectManager $manager): void
    {
        /** @var User $user */
        $user = $this->getReference(LoadUser::USER);

        $file = new File();
        $file->setFile(new ComponentFile(__DIR__ . '/../../DataFixtures/files/image.jpg'));
        $file->setOriginalFilename('image_1.jpg');
        $file->setOwner($user);
        $manager->persist($file);
        $this->setReference('image_1', $file);

        $file = new File();
        $file->setFile(new ComponentFile(__DIR__ . '/../../DataFixtures/files/image.jpg'));
        $file->setOriginalFilename('image_2.jpg');
        $file->setOwner($user);
        $manager->persist($file);
        $this->setReference('image_2', $file);

        $file = new File();
        $file->setFile(new ComponentFile(__DIR__ . '/../../DataFixtures/files/image.jpg'));
        $file->setOriginalFilename('image_3.jpg');
        $file->setOwner($user);
        $manager->persist($file);
        $this->setReference('image_3', $file);
    }

    private function createFileItem(File $file, int $sortOrder): FileItem
    {
        $fileItem = new FileItem();
        $fileItem->setFile($file);
        $fileItem->setSortOrder($sortOrder);

        return $fileItem;
    }
}
