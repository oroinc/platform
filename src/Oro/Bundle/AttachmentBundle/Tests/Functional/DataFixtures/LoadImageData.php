<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Functional\DataFixtures;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\AbstractFixture;
use Oro\Bundle\UserBundle\DataFixtures\UserUtilityTrait;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\File\File as ComponentFile;

class LoadImageData extends AbstractFixture
{
    use UserUtilityTrait;

    public const IMAGE_JPG = 'image_jpg';
    public const IMAGE_WEBP = 'image_webp';
    public const IMAGE_EXTERNAL = 'image_external';

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager): void
    {
        $file = new File();
        $file->setFile(new ComponentFile(__DIR__ . '/files/image.jpg'));
        $file->setParentEntityClass(User::class);
        $file->setParentEntityId($this->getFirstUser($manager)->getId());
        $file->setParentEntityFieldName('avatar');
        $manager->persist($file);
        $this->setReference(self::IMAGE_JPG, $file);

        $file = new File();
        $file->setFile(new ComponentFile(__DIR__ . '/files/image.webp'));
        $file->setParentEntityClass(User::class);
        $file->setParentEntityId($this->getFirstUser($manager)->getId());
        $file->setParentEntityFieldName('avatar');
        $manager->persist($file);
        $this->setReference(self::IMAGE_WEBP, $file);

        $file = new File();
        $file->setFilename(self::IMAGE_EXTERNAL);
        $file->setParentEntityClass(User::class);
        $file->setParentEntityId($this->getFirstUser($manager)->getId());
        $file->setParentEntityFieldName('avatar');
        $file->setExternalUrl('https://example.org/child.file');
        $manager->persist($file);
        $this->setReference(self::IMAGE_EXTERNAL, $file);

        $manager->flush();
    }
}
