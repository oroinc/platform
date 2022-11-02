<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadUser;

class LoadInvalidFileFixture extends AbstractFixture implements DependentFixtureInterface
{
    public const INVALID_FILE_1 = 'invalid_file';

    /**
     * {@inheritdoc}
     */
    public function getDependencies(): array
    {
        return [
            LoadOrganization::class,
            LoadUser::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager): void
    {
        $invalidFile = (new File())
            ->setUuid('74d27cad-b800-4d71-833e-775d01aebeba')
            ->setFilename('invalid/filepath.jpg');

        $this->setReference(self::INVALID_FILE_1, $invalidFile);

        $manager->persist($invalidFile);
        $manager->flush();
    }
}
