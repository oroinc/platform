<?php

namespace Oro\Bundle\DigitalAssetBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\DigitalAssetBundle\Entity\DigitalAsset;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;
use Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\AbstractFixture;
use Oro\Bundle\UserBundle\DataFixtures\UserUtilityTrait;
use Oro\Bundle\UserBundle\Tests\Functional\DataFixtures\LoadUsersWithAvatars;

class LoadUsersAvatarsDigitalAssets extends AbstractFixture implements DependentFixtureInterface
{
    use UserUtilityTrait;

    /**
     * {@inheritdoc}
     */
    public function getDependencies(): array
    {
        return [
            LoadUsersWithAvatars::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager): void
    {
        // Creates file with invalid reference to digital asset.
        $fileWithInvalidReferenceToDigitalAsset = (new File())
            ->setUuid('07bad972-48c9-4ba9-8cb3-eb595ab2d069')
            ->setFilename('sample_filename.png')
            ->setParentEntityClass(DigitalAsset::class)
            ->setParentEntityFieldName('sourceFile')
            ->setParentEntityId(999999);

        $manager->persist($fileWithInvalidReferenceToDigitalAsset);
        $this->setReference('file_with_invalid_digital_asset', $fileWithInvalidReferenceToDigitalAsset);

        // Creates valid digital asset from user2 avatar.
        $user2 = $this->getReference('user2');
        $avatar2File = $user2->getAvatar();
        $sourceFile = $this->container->get('oro_attachment.file_manager')->cloneFileEntity($avatar2File);

        $digitalAsset = $this->createDigitalAsset($manager, $sourceFile, 'user_2_avatar_digital_asset');

        $avatar2File->setDigitalAsset($digitalAsset);
        $manager->persist($avatar2File);

        $manager->flush();
    }

    private function createDigitalAsset(ObjectManager $manager, File $sourceFile, string $name): DigitalAsset
    {
        $user = $this->getFirstUser($manager);

        /** @var OrganizationInterface $organization */
        $organization = $this->getReference('organization');

        $digitalAsset = new DigitalAsset();
        $digitalAsset->setSourceFile($sourceFile);
        $digitalAsset->setOwner($user);
        $digitalAsset->setOrganization($organization);

        $manager->persist($digitalAsset);
        $this->setReference($name, $digitalAsset);

        return $digitalAsset;
    }
}
