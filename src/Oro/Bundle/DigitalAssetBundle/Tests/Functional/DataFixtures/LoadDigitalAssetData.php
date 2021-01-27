<?php

namespace Oro\Bundle\DigitalAssetBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\DigitalAssetBundle\Entity\DigitalAsset;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;
use Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\AbstractFixture;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;
use Oro\Bundle\UserBundle\DataFixtures\UserUtilityTrait;

class LoadDigitalAssetData extends AbstractFixture implements DependentFixtureInterface
{
    use UserUtilityTrait;

    public const DIGITAL_ASSET_1 = 'digital_asset_1';
    public const DIGITAL_ASSET_1_SOURCE = 'digital_asset_1_source';
    public const DIGITAL_ASSET_1_CHILD_1 = 'digital_asset_1_child_1';
    public const DIGITAL_ASSET_1_CHILD_2 = 'digital_asset_1_child_2';

    public const DIGITAL_ASSET_2 = 'digital_asset_2';
    public const DIGITAL_ASSET_2_SOURCE = 'digital_asset_2_source';
    public const DIGITAL_ASSET_2_CHILD_1 = 'digital_asset_2_child_1';
    public const DIGITAL_ASSET_2_CHILD_2 = 'digital_asset_2_child_2';

    public const DIGITAL_ASSET_3 = 'digital_asset_3';
    public const DIGITAL_ASSET_3_SOURCE = 'digital_asset_3_source';

    public const REFERENCE_MAPPING = [
        self::DIGITAL_ASSET_1 => [
            'source' => self::DIGITAL_ASSET_1_SOURCE,
            'children' => [
                self::DIGITAL_ASSET_1_CHILD_1 => [
                    'class' => \stdClass::class,
                    'id' => 1,
                    'field' => 'attachmentFieldA',
                ],
                self::DIGITAL_ASSET_1_CHILD_2 => [
                    'class' => \stdClass::class,
                    'id' => 42,
                    'field' => 'attachmentFieldB',
                ],
            ],
        ],
        self::DIGITAL_ASSET_2 => [
            'source' => self::DIGITAL_ASSET_2_SOURCE,
            'children' => [
                self::DIGITAL_ASSET_2_CHILD_1 => [
                    'class' => \stdClass::class,
                    'id' => 1,
                    'field' => 'attachmentFieldA',
                ],
                self::DIGITAL_ASSET_2_CHILD_2 => [
                    'class' => \stdClass::class,
                    'id' => 42,
                    'field' => 'attachmentFieldC',
                ],
            ],
        ],
        self::DIGITAL_ASSET_3 => [
            'source' => self::DIGITAL_ASSET_3_SOURCE,
            'children' => [],
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function getDependencies(): array
    {
        return [
            LoadOrganization::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager): void
    {
        $user = $this->getFirstUser($manager);

        /** @var OrganizationInterface $organization */
        $organization = $this->getReference('organization');

        foreach (self::REFERENCE_MAPPING as $assetRef => $mapping) {
            $sourceFile = new File();
            $sourceFile->setFilename('source.file');
            $this->setReference($mapping['source'], $sourceFile);

            $digitalAsset = new DigitalAsset();
            $digitalAsset->setSourceFile($sourceFile);
            $digitalAsset->setOwner($user);
            $digitalAsset->setOrganization($organization);
            $manager->persist($digitalAsset);
            $this->setReference($assetRef, $digitalAsset);

            foreach ($mapping['children'] as $childRef => $childMapping) {
                $childFile = new File();
                $childFile->setFilename('child.file');
                $childFile->setParentEntityClass($childMapping['class']);
                $childFile->setParentEntityId($childMapping['id']);
                $childFile->setParentEntityFieldName($childMapping['field']);
                $childFile->setDigitalAsset($digitalAsset);
                $manager->persist($childFile);
                $this->setReference($childRef, $childFile);
            }
        }

        $manager->flush();
    }
}
