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
            'attribute' => [
                'is_system' => true,
            ],
        ],
        self::SYSTEM_ATTRIBUTE_2 => [
            'attribute' => [
                'is_system' => true,
            ],
        ],
        self::DELETED_SYSTEM_ATTRIBUTE => [
            'attribute' => [
                'is_system' => true,
            ],
            'extend' => [
                'state' => ExtendScope::STATE_DELETE,
            ]
        ],
        self::REGULAR_ATTRIBUTE_1 => [
            'attribute' => [
                'is_system' => false,
            ],
        ],
        self::REGULAR_ATTRIBUTE_2 => [
            'attribute' => [
                'is_system' => false,
            ],
        ],
        self::NOT_USED_ATTRIBUTE => [
            'attribute' => [
                'is_system' => false,
            ],
        ],
        self::DELETED_REGULAR_ATTRIBUTE => [
            'attribute' => [
                'is_system' => false,
            ],
            'extend' => [
                'state' => ExtendScope::STATE_DELETE,
            ]
        ],
    ];

    /**
     * @var array
     */
    private static $attributesData = [];

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $modelManager = $this->container->get('oro_entity_config.attribute.config_model_manager');
        $configManager = $this->container->get('oro_entity_config.config_manager');
        $configHelper = $this->container->get('oro_entity_config.config.config_helper');

        $entityModel = $modelManager->getEntityModel(self::ENTITY_CONFIG_MODEL);
        $persistedAttributes = [];
        foreach (self::$attributes as $attributeName => $attributeOptions) {
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

            $modelManager->getEntityManager()->persist($attribute);
            $persistedAttributes[$attributeName] = $attribute;
        }

        $modelManager->getEntityManager()->flush();

        foreach ($persistedAttributes as $attributeName => $attribute) {
            self::$attributesData[$attributeName] = $attribute->getId();
        }
    }

    /**
     * @param string $attributeName
     * @return int|null
     */
    public static function getAttributeIdByName($attributeName)
    {
        return isset(self::$attributesData[$attributeName]) ? self::$attributesData[$attributeName] : null;
    }
}
