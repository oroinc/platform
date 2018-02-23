<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroup;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroupRelation;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class LoadAttributeGroupData extends AbstractFixture implements DependentFixtureInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    const DEFAULT_ATTRIBUTE_GROUP_1 = 'default_attribute_group_1';
    const DEFAULT_ATTRIBUTE_GROUP_2 = 'default_attribute_group_2';
    const REGULAR_ATTRIBUTE_GROUP_1 = 'regular_attribute_group_1';
    const REGULAR_ATTRIBUTE_GROUP_2 = 'regular_attribute_group_2';
    const EMPTY_ATTRIBUTE_GROUP = 'empty_attribute_group';

    /** @var array */
    protected $groups = [
        'default' => [
            self::DEFAULT_ATTRIBUTE_GROUP_1 => [
                LoadAttributeData::SYSTEM_ATTRIBUTE_1,
                LoadAttributeData::SYSTEM_ATTRIBUTE_2,
            ],
            self::DEFAULT_ATTRIBUTE_GROUP_2 => [
                LoadAttributeData::SYSTEM_ATTRIBUTE_1,
                LoadAttributeData::SYSTEM_ATTRIBUTE_2,
            ],
        ],
        'regular' => [
            self::REGULAR_ATTRIBUTE_GROUP_1 => [
                LoadAttributeData::REGULAR_ATTRIBUTE_1,
            ],
            self::REGULAR_ATTRIBUTE_GROUP_2 => [
                LoadAttributeData::REGULAR_ATTRIBUTE_2,
            ],
            self::EMPTY_ATTRIBUTE_GROUP => [],
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadAttributeData::class,
        ];
    }

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $configManager = $this->container->get('oro_entity_config.config_manager');
        foreach ($this->groups as $type => $groups) {
            foreach ($groups as $groupName => $attributes) {
                $group = new AttributeGroup();
                $group->setDefaultLabel($groupName);

                foreach ($attributes as $attributeName) {
                    $attribute = $configManager->getConfigFieldModel(
                        LoadAttributeData::ENTITY_CONFIG_MODEL,
                        $attributeName
                    );
                    $relation = new AttributeGroupRelation();
                    $relation->setAttributeGroup($group);
                    $relation->setEntityConfigFieldId($attribute->getId());
                    $manager->persist($relation);
                    $group->addAttributeRelation($relation);
                }

                $this->setReference($groupName, $group);
                $manager->persist($group);
            }
        }
        $manager->flush();
    }
}
