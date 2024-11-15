<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\AbstractFixture;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadUser;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\File\File as ComponentFile;

class LoadImageData extends AbstractFixture implements DependentFixtureInterface
{
    public const IMAGE_JPG = 'image_jpg';
    public const IMAGE_WEBP = 'image_webp';
    public const IMAGE_EXTERNAL = 'image_external';

    #[\Override]
    public function getDependencies(): array
    {
        return [
            LoadUser::class
        ];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        /** @var User $user */
        $user = $this->getReference(LoadUser::USER);

        $file = new File();
        $file->setFile(new ComponentFile(__DIR__ . '/files/image.jpg'));
        $file->setParentEntityClass(User::class);
        $file->setParentEntityId($user->getId());
        $file->setParentEntityFieldName('avatar');
        $manager->persist($file);
        $this->setReference(self::IMAGE_JPG, $file);

        $file = new File();
        $file->setFile(new ComponentFile(__DIR__ . '/files/image.webp'));
        $file->setParentEntityClass(User::class);
        $file->setParentEntityId($user->getId());
        $file->setParentEntityFieldName('avatar');
        $manager->persist($file);
        $this->setReference(self::IMAGE_WEBP, $file);

        $file = new File();
        $file->setFilename(self::IMAGE_EXTERNAL);
        $file->setParentEntityClass(User::class);
        $file->setParentEntityId($user->getId());
        $file->setParentEntityFieldName('avatar');
        $file->setExternalUrl('https://example.org/child.file');
        $manager->persist($file);
        $this->setReference(self::IMAGE_EXTERNAL, $file);

        $manager->flush();
    }
}
