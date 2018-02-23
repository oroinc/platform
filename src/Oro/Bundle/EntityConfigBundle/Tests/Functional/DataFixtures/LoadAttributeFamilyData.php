<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroup;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadAdminUserData;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class LoadAttributeFamilyData extends AbstractFixture implements DependentFixtureInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    const ATTRIBUTE_FAMILY_1 = 'attribute_family_1';
    const ATTRIBUTE_FAMILY_2 = 'attribute_family_2';

    /** @var array */
    protected $families = [
        self::ATTRIBUTE_FAMILY_1 => [
            LoadAttributeGroupData::DEFAULT_ATTRIBUTE_GROUP_1,
            LoadAttributeGroupData::REGULAR_ATTRIBUTE_GROUP_1,
        ],
        self::ATTRIBUTE_FAMILY_2 => [
            LoadAttributeGroupData::DEFAULT_ATTRIBUTE_GROUP_2,
            LoadAttributeGroupData::REGULAR_ATTRIBUTE_GROUP_2,
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadAttributeGroupData::class,
        ];
    }

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $configManager = $this->container->get('oro_entity_config.config_manager');
        /** @var EntityConfigModel $entityConfigModel */
        $entityConfigModel = $configManager->getConfigEntityModel(LoadAttributeData::ENTITY_CONFIG_MODEL);
        foreach ($this->families as $familyName => $groups) {
            $family = new AttributeFamily();
            $family->setDefaultLabel($familyName);
            $family->setOwner($this->getAdminUser($manager));
            $family->setCode($familyName);
            $family->setEntityClass($entityConfigModel->getClassName());
            foreach ($groups as $group) {
                /** @var AttributeGroup $group */
                $group = $this->getReference($group);
                $group->setAttributeFamily($family);
                $manager->persist($group);
                $family->addAttributeGroup($group);
            }

            $this->setReference($familyName, $family);
            $manager->persist($family);
        }
        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @return User
     */
    private function getAdminUser(ObjectManager $manager)
    {
        $repository = $manager->getRepository(User::class);

        return $repository->findOneBy(['email' => LoadAdminUserData::DEFAULT_ADMIN_EMAIL]);
    }
}
