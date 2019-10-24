<?php

namespace Oro\Bundle\DigitalAssetBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\DigitalAssetBundle\Entity\DigitalAsset;
use Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\AbstractFixture;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;
use Oro\Bundle\UserBundle\DataFixtures\UserUtilityTrait;

class LoadDigitalAssetData extends AbstractFixture implements DependentFixtureInterface
{
    use UserUtilityTrait;

    public const DIGITAL_ASSET_1 = 'digital_asset_1';

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager): void
    {
        $sourceFile = new File();
        $sourceFile->setFilename('digital/asset/source.file');
        $manager->persist($sourceFile);

        $digitalAsset = new DigitalAsset();
        $digitalAsset->setSourceFile($sourceFile);
        $digitalAsset->setOwner($this->getFirstUser($manager));
        $digitalAsset->setOrganization($this->getReference('organization'));
        $manager->persist($digitalAsset);

        $this->setReference(self::DIGITAL_ASSET_1, $digitalAsset);

        for ($i = 0; $i < 2; $i++) {
            $childFile = new File();
            $childFile->setFilename('digital/asset/child.file' . $i);
            $childFile->setDigitalAsset($digitalAsset);
            $manager->persist($childFile);
        }

        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies(): array
    {
        return [
            LoadOrganization::class,
        ];
    }
}
