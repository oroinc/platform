<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\TestFrameworkBundle\Entity\TestActivityTarget;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class LoadAttributeData extends AbstractFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    const ENTITY_CONFIG_MODEL = TestActivityTarget::class;
    const SYSTEM_ATTRIBUTE_1 = 'system_attribute_1';
    const SYSTEM_ATTRIBUTE_2 = 'system_attribute_2';
    const DELETED_SYSTEM_ATTRIBUTE = 'deleted_system_attribute';
    const REGULAR_ATTRIBUTE_1 = 'regular_attribute_1';
    const REGULAR_ATTRIBUTE_2 = 'regular_attribute_2';
    const NOT_USED_ATTRIBUTE = 'not_used_attribute';
    const DELETED_REGULAR_ATTRIBUTE = 'deleted_regular_attribute';

    /**
     * @var array
     */
    protected static $attributes = [
        self::SYSTEM_ATTRIBUTE_1 => [
            'extend' => [
                'owner' => ExtendScope::OWNER_SYSTEM,
                'origin' => ExtendScope::ORIGIN_CUSTOM, //needed to apply changes set to extend scope
                'state' => ExtendScope::STATE_ACTIVE,
            ]
        ],
        self::SYSTEM_ATTRIBUTE_2 => [
            'extend' => [
                'owner' => ExtendScope::OWNER_SYSTEM,
                'origin' => ExtendScope::ORIGIN_CUSTOM,
                'state' => ExtendScope::STATE_ACTIVE,
            ]
        ],
        self::DELETED_SYSTEM_ATTRIBUTE => [
            'extend' => [
                'state' => ExtendScope::STATE_DELETE,
                'owner' => ExtendScope::OWNER_SYSTEM,
            ]
        ],
        self::REGULAR_ATTRIBUTE_1 => [
            'extend' => [
                'owner' => ExtendScope::OWNER_CUSTOM,
                'state' => ExtendScope::STATE_ACTIVE,
            ]
        ],
        self::REGULAR_ATTRIBUTE_2 => [
            'extend' => [
                'owner' => ExtendScope::OWNER_CUSTOM,
                'state' => ExtendScope::STATE_ACTIVE,
            ]
        ],
        self::NOT_USED_ATTRIBUTE => [
            'extend' => [
                'owner' => ExtendScope::OWNER_CUSTOM,
                'state' => ExtendScope::STATE_NEW
            ]
        ],
        self::DELETED_REGULAR_ATTRIBUTE => [
            'extend' => [
                'state' => ExtendScope::STATE_DELETE,
                'owner' => ExtendScope::OWNER_CUSTOM,
            ]
        ],
    ];

    /**
     * @var array
     */
    protected static $attributesData = [];

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $configManager = $this->container->get('oro_entity_config.config_manager');
        $configHelper = $this->container->get('oro_entity_config.config.config_helper');
        $entityModel = $configManager->getConfigEntityModel(self::ENTITY_CONFIG_MODEL);
        $entityManager = $configManager->getEntityManager();

        $persistedAttributes = [];
        foreach (static::$attributes as $attributeName => $attributeOptions) {
            $attribute = $configManager->createConfigFieldModel(
                $entityModel->getClassName(),
                $attributeName,
                'string'
            );
            $attribute->setCreated(new \DateTime());
            $options = array_merge_recursive(
                $attributeOptions,
                [
                    'attribute' => [
                        'is_attribute' => true
                    ]
                ]
            );
            $configHelper->updateFieldConfigs($attribute, $options);

            $entityManager->persist($attribute);
            $persistedAttributes[$attributeName] = $attribute;
        }

        $configManager->flush();

        foreach ($persistedAttributes as $attributeName => $attribute) {
            static::$attributesData[$attributeName] = $attribute->getId();
        }
    }

    /**
     * @param string $attributeName
     * @return int|null
     */
    public static function getAttributeIdByName($attributeName)
    {
        return isset(static::$attributesData[$attributeName]) ? static::$attributesData[$attributeName] : null;
    }
}
