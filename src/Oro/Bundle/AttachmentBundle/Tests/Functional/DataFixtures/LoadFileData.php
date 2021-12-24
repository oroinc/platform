<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Functional\DataFixtures;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\AbstractFixture;
use Oro\Bundle\UserBundle\DataFixtures\UserUtilityTrait;
use Symfony\Component\HttpFoundation\File\File as ComponentFile;

class LoadFileData extends AbstractFixture
{
    use UserUtilityTrait;

    public const FILE_1 = 'file_1';
    public const FILE_2 = 'file_2';
    public const FILE_3 = 'file_3';

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager): void
    {
        $file = new File();
        $file->setFile(new ComponentFile(__DIR__ . '/files/file_1.txt'));
        $file->setParentEntityClass(\stdClass::class);
        $file->setParentEntityId(1);
        $file->setParentEntityFieldName('fieldA');
        $manager->persist($file);
        $this->setReference(self::FILE_1, $file);

        $file = new File();
        $file->setFile(new ComponentFile(__DIR__ . '/files/file_2.txt'));
        $file->setOriginalFilename('file_2.txt');
        $file->setParentEntityClass(\stdClass::class);
        $file->setParentEntityId(2);
        $file->setParentEntityFieldName('fieldB');
        $manager->persist($file);
        $this->setReference(self::FILE_2, $file);

        $file = new File();
        $file->setFile(new ComponentFile(__DIR__ . '/files/file_3.txt'));
        $file->setParentEntityClass(\stdClass::class);
        $file->setParentEntityId(1);
        $file->setParentEntityFieldName('fieldC');
        $manager->persist($file);
        $this->setReference(self::FILE_3, $file);

        $manager->flush();
    }
}
